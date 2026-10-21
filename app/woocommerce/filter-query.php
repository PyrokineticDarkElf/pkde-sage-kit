<?php

/**
 * Wires the sidebar shop filters (see ShopFilters.php) into the main product
 * query, and migrates legacy WooCommerce filter URLs onto the theme's own
 * query parameters.
 *
 * This file is loaded from app/woocommerce.php only after WooCommerce has
 * confirmed it is active.
 */

namespace App;

/*
 * WooCommerce reserves filter_product_cat/tag for its own scalar product
 * filters. Preserve any in-flight URLs from the earlier custom controls, but
 * move their array values out of WooCommerce's parameter namespace before it
 * builds the main query.
 */
/** @param \WP|null $wp */
$migrateLegacyFilterParameters = function ($wp = null) {
    foreach (['product_cat', 'product_tag'] as $taxonomy) {
        $legacyKey = 'filter_' . $taxonomy;
        $themeKey = ShopFilters::queryKey($taxonomy);

        if (isset($_GET[$legacyKey]) && is_array($_GET[$legacyKey])) {
            $_GET[$themeKey] = $_GET[$themeKey] ?? $_GET[$legacyKey];
            $_REQUEST[$themeKey] = $_REQUEST[$themeKey] ?? $_GET[$legacyKey];
            unset($_GET[$legacyKey]);
            unset($_REQUEST[$legacyKey]);
        }

        // WP::parse_request() stores query parameters here before the product
        // query is built; clear the reserved array key at that layer too.
        if ($wp instanceof \WP && isset($wp->query_vars[$legacyKey]) && is_array($wp->query_vars[$legacyKey])) {
            $wp->query_vars[$themeKey] = $wp->query_vars[$themeKey] ?? $wp->query_vars[$legacyKey];
            unset($wp->query_vars[$legacyKey]);
        }
    }
};

add_action('init', $migrateLegacyFilterParameters, 1);
add_action('parse_request', $migrateLegacyFilterParameters, 1);

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
 * Faceted URLs: any filtered view of a product list (a sage_filter_* choice or a ?sage_view=) is `noindex, follow`, so
 * search engines do not index the endless combinations but still follow the product links on them.
 */
add_filter('wp_robots', function ($robots) {
    if (is_faceted_request($_GET)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
        unset($robots['index']);
    }

    return $robots;
}, 20);
