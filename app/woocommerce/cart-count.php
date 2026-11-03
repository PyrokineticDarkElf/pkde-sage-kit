<?php

/**
 * The header's basket count, kept live (owner, TPL-147). The site's header prints cart_count_html() inside its badge, and the badge carries
 * `data-pkde-cart-badge`. The count is registered as a WooCommerce cart fragment, so WooCommerce's own add-to-cart script swaps in the new number
 * after every AJAX add to basket (product cards, related products); resources/js/cart-count.js does the same after a change on the basket page,
 * which WooCommerce's cart script does not. The count is empty at 0 and the badge hides itself then (shop-pages.css), so it appears at the
 * first add without a reload.
 */

namespace App;

/** The count element: the number of items, or empty when the basket is empty. */
function cart_count_markup(int $count): string
{
    return '<span class="pkde-cart-count">' . ($count > 0 ? $count : '') . '</span>';
}

/** The current visitor's count element. */
function cart_count_html(): string
{
    return cart_count_markup(function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0);
}

if (! function_exists('add_filter')) {
    return;
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['span.pkde-cart-count'] = cart_count_html();

    return $fragments;
});
