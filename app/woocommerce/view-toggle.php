<?php

/**
 * Grid / list view toggle on the shop and the product archives (MGR-70, owner). Two buttons next to the sort (aria-pressed, plain buttons, so the
 * keyboard works), the choice kept in the visitor's browser (localStorage, so a cached page is never personalised: the markup is the same for
 * everyone and resources/js/shop-view.js applies the choice), and a list variant of the product card drawn by CSS from the same markup
 * (resources/css/shop-pages.css: image left, title, rating, short description, price and the action row on the right; stacked on a phone). Grid is the
 * default. A tiny head script sets the saved view before the first paint so a list-view visitor does not see the grid flash.
 *
 * Off with `add_filter('pkde_kit/shop_view_toggle', '__return_false')`. A site with its own card view keeps the card and gets the list variant by
 * giving it the classes the CSS uses: `pkde-product-card` (the li), `pkde-product-card__media`, `pkde-product-card__body`, `pkde-product-card__excerpt`
 * and the `pkde-product-actions` row.
 */

namespace App;

/** Whether this request shows a product list with the toggle: the shop, a product taxonomy or a product search, unless the filter says no. */
function shop_view_toggle_enabled(): bool
{
    $isProductList = (function_exists('is_shop') && is_shop())
        || (function_exists('is_product_taxonomy') && is_product_taxonomy())
        || (function_exists('is_post_type_archive') && is_post_type_archive('product'));

    return $isProductList && (bool) apply_filters('pkde_kit/shop_view_toggle', true);
}

/** The two buttons. Same HTML for every visitor (page-cache safe); the script sets aria-pressed from the saved choice. */
function shop_view_toggle_html(): string
{
    $grid = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>';
    $list = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 4h18v4H3V4zm0 6h18v4H3v-4zm0 6h18v4H3v-4z"/></svg>';

    return sprintf(
        '<div class="pkde-view-toggle" role="group" aria-label="%1$s">'
        . '<button type="button" class="pkde-view-toggle__button" data-pkde-view-set="grid" aria-pressed="true">%2$s<span class="sr-only">%3$s</span></button>'
        . '<button type="button" class="pkde-view-toggle__button" data-pkde-view-set="list" aria-pressed="false">%4$s<span class="sr-only">%5$s</span></button>'
        . '</div>',
        esc_attr(__('Product view', 'sage')),
        $grid,
        esc_html(__('Grid view', 'sage')),
        $list,
        esc_html(__('List view', 'sage')),
    );
}

if (! function_exists('add_action')) {
    return;
}

// The saved view, applied before the first paint. A one-line inline script, only on a page with the toggle.
add_action('wp_head', function () {
    if (shop_view_toggle_enabled()) {
        echo "<script>try{var v=localStorage.getItem('pkde_shop_view');if(v==='list'||v==='grid'){document.documentElement.dataset.pkdeView=v}}catch(e){}</script>\n";
    }
}, 1);

// A toolbar around WooCommerce's result count (20) and sort (30), with the toggle at the end.
add_action('woocommerce_before_shop_loop', function () {
    if (shop_view_toggle_enabled()) {
        echo '<div class="pkde-shop-toolbar">';
    }
}, 15);
add_action('woocommerce_before_shop_loop', function () {
    if (shop_view_toggle_enabled()) {
        echo shop_view_toggle_html(); // phpcs:ignore -- built from escaped parts above
    }
}, 35);
add_action('woocommerce_before_shop_loop', function () {
    if (shop_view_toggle_enabled()) {
        echo '</div>';
    }
}, 40);
