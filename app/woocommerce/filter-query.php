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
 * Shop sidebar controls submit selected taxonomy slugs as GET arrays. Each
 * taxonomy uses IN (any selected term), while separate filter groups combine
 * with AND. This keeps filters composable and URLs bookmarkable/shareable.
 */
add_action('pre_get_posts', function ($query) {
    if (is_admin() || ! $query->is_main_query() || ! ($query->is_post_type_archive('product') || $query->is_tax(['product_cat', 'product_tag', 'product_brand']))) {
        return;
    }

    $taxQuery = (array) $query->get('tax_query');

    foreach (ShopFilters::taxonomies() as $taxonomy => $label) {
        $selected = ShopFilters::activeTerms($taxonomy);

        if ($selected) {
            $taxQuery[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $selected,
                'operator' => 'IN',
            ];
        }
    }

    if (count($taxQuery) > 1 && ! isset($taxQuery['relation'])) {
        $taxQuery['relation'] = 'AND';
    }

    $query->set('tax_query', $taxQuery);
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
