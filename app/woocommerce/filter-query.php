<?php

/**
 * Wires the sidebar shop filters (see ShopFilters.php) into the main product
 * query. (The migration of the earlier filter_product_cat[] / filter_product_tag[]
 * array URLs onto sage_filter_* was dropped in 2026.11.19.1, TPL-185.)
 *
 * This file is loaded from app/woocommerce.php only after WooCommerce has
 * confirmed it is active.
 */

namespace App;

/**
 * A request that carries sage_filter_* arguments is checked before ANY product query runs (WP-96, C): too many values or groups, or only unknown values,
 * is a 404 with no query and no filter UI; a request that is valid but not canonical (values unsorted or repeated, an unknown parameter or value dropped, a bad
 * price) is a 301 to its one canonical URL, so identical choices share one URL and one page-cache entry. See App\ShopFilterRequest.
 */
add_action('pre_get_posts', function ($query) {
    if (is_admin() || ! $query->is_main_query() || ! array_filter(array_keys($_GET), fn($key) => str_starts_with((string) $key, ShopFilterRequest::PREFIX))) {
        return;
    }

    $request = ShopFilters::request();

    if ($request['status'] === 'redirect') {
        $path = (string) strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
        $queryString = http_build_query($request['args']);
        wp_safe_redirect($path . ($queryString !== '' ? '?' . $queryString : ''), 301);
        exit;
    }

    if ($request['status'] === 'notfound') {
        $query->set_404();
        status_header(404);
        nocache_headers();
        add_filter('posts_pre_query', fn($posts, $q) => $q === $query ? [] : $posts, 10, 2);
    }
}, 1);

/**
 * The validated choices become tax-query clauses on the main product query. Each group is IN (any chosen term; a hierarchical taxonomy includes the children),
 * different groups combine with AND. The clauses are flagged `pkde_filter` so the counts can take exactly these out and keep every other condition of the
 * query (ShopFilterCounts).
 */
add_action('pre_get_posts', function ($query) {
    // The shop, a category, tag or brand archive, and a product search (a search used to ignore the sidebar's choices while the sidebar's counts assumed them).
    $productSearch = $query->is_search() && in_array('product', (array) $query->get('post_type'), true);

    if (is_admin() || ! $query->is_main_query() || ! ($query->is_post_type_archive('product') || $query->is_tax(['product_cat', 'product_tag', 'product_brand']) || $productSearch)) {
        return;
    }

    $request = ShopFilters::request();
    $taxQuery = (array) $query->get('tax_query');
    $before = count($taxQuery);

    foreach ($request['selection'] as $taxonomy => $slugs) {
        $taxQuery[] = [
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $slugs,
            'operator' => 'IN',
            'pkde_filter' => true,
        ];
    }

    if ($request['stock']) {
        $taxQuery[] = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['outofstock'], 'operator' => 'NOT IN', 'pkde_filter' => true];
    }

    if (count($taxQuery) === $before) {
        return;
    }

    if (count($taxQuery) > 1 && ! isset($taxQuery['relation'])) {
        $taxQuery['relation'] = 'AND';
    }

    $query->set('tax_query', $taxQuery);
});

/**
 * Cached counts are keyed by a catalogue version; it moves when a product is saved, deleted or changes stock, and when a product term or attribute is edited.
 * Once per request however many of these fire (a product save fires several).
 */
$bumpFilterVersion = function () {
    static $done = false;
    if (! $done) {
        $done = true;
        ShopFilterCounts::bumpVersion();
    }
};
foreach (['save_post_product', 'save_post_product_variation', 'before_delete_post', 'trashed_post', 'untrashed_post', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status', 'woocommerce_attribute_added', 'woocommerce_attribute_updated', 'woocommerce_attribute_deleted', 'update_option_woocommerce_hide_out_of_stock_items'] as $hook) {
    add_action($hook, $bumpFilterVersion);
}
foreach (['created_term', 'edited_term', 'delete_term'] as $hook) {
    add_action($hook, function ($termId, $ttId = 0, $taxonomy = '') use ($bumpFilterVersion) {
        if ($taxonomy === '' || is_object_in_taxonomy('product', (string) $taxonomy)) {
            $bumpFilterVersion();
        }
    }, 10, 3);
}
add_action('set_object_terms', function ($objectId, $terms, $ttIds, $taxonomy) use ($bumpFilterVersion) {
    if (get_post_type((int) $objectId) === 'product') {
        $bumpFilterVersion();
    }
}, 10, 4);

/** A declared filter source that is not a product taxonomy, price or stock is skipped, never fatal; an administrator is told (the drift check fails it too). */
add_action('admin_notices', function () {
    $invalid = current_user_can('manage_options') ? ShopFilters::invalidSources() : [];

    if ($invalid) {
        printf('<div class="notice notice-warning"><p>%s</p></div>', esc_html(sprintf(
            /* translators: %s: a list of filter sources from config/shop-filters.php */
            __('Shop filters: these sources in config/shop-filters.php are not product taxonomies, price or stock, and are skipped: %s', 'sage'),
            implode(', ', $invalid),
        )));
    }
});

/**
 * `?featured=1` on the Shop page — powers the Featured Products block's own
 * "View all" link (see resources/blocks/featured-products/render.php).
 * WooCommerce's classic shop template has no built-in way to filter to just
 * featured products via URL (that's block-theme/shortcode-only otherwise).
 * Reuses wc_get_featured_product_ids() — the same source the block itself
 * queries — so "view all" genuinely shows the same set the block already
 * proved has content, not a second, possibly-different definition of
 * "featured".
 */
add_action('pre_get_posts', function ($query) {
    if (is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive('product') || empty($_GET['featured'])) {
        return;
    }

    $query->set('post__in', wc_get_featured_product_ids() ?: [0]);
});

/**
 * `?sage_view=sale` and `?sage_view=new`: the targets of the Sale and New badges on product cards. Sale is every product on
 * sale; New is the same few most recent products the badge is drawn on (RecentContent). They are views of the product list,
 * not pages of their own, so search engines are told to leave them out (see the robots filter below).
 */
add_action('pre_get_posts', function ($query) {
    $view = isset($_GET['sage_view']) && is_string($_GET['sage_view']) ? sanitize_key($_GET['sage_view']) : '';

    if (is_admin() || ! $query->is_main_query() || ! in_array($view, ['sale', 'new'], true) || ! ($query->is_post_type_archive('product') || $query->is_tax(['product_cat', 'product_tag', 'product_brand']))) {
        return;
    }

    $ids = $view === 'sale' ? wc_get_product_ids_on_sale() : RecentContent::recentIds('product');
    $query->set('post__in', $ids ?: [0]);
});

/**
 * Faceted URLs (see is_faceted_request(), the one rule for which query arguments make one): `noindex, follow`, so search
 * engines do not index the endless combinations but still follow the product links on them. Three places say it, all from
 * that one rule, because each one alone misses a case:
 *  - the X-Robots-Tag header, the base: it holds whatever SEO plugin is active (or none) and reaches non-HTML responses;
 *  - the wp_robots tag, for a site without Rank Math (Rank Math removes every wp_robots filter, class-head.php);
 *  - Rank Math's own robots meta, so it never says `index` where the header says `noindex`.
 */
add_action('send_headers', function () {
    if (! headers_sent() && is_faceted_request($_GET)) {
        header('X-Robots-Tag: noindex, follow');
    }
});

add_filter('wp_robots', function ($robots) {
    if (is_faceted_request($_GET)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
        unset($robots['index']);
    }

    return $robots;
}, 20);

add_filter('rank_math/frontend/robots', fn($robots) => is_array($robots) ? faceted_rank_math_robots($robots, $_GET) : $robots, 20);
