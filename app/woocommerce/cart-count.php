<?php

/**
 * The header's basket count, kept live (owner, TPL-147). The site's header prints cart_count_html() inside its badge, and the badge carries
 * `data-pkde-cart-badge`. The count is registered as a WooCommerce cart fragment, so WooCommerce's own add-to-cart script swaps in the new number
 * after every AJAX add to basket (product cards, related products); resources/js/cart-count.js does the same after a change on the basket page,
 * which WooCommerce's cart script does not. The count is empty at 0 and the badge hides itself then (shop-pages.css), so it appears at the
 * first add without a reload. The header names its link with visually hidden text (not aria-label), so the count is part of the link's
 * accessible name: "Cart" when empty, "Cart, 2 items" (TPL-148).
 */

namespace App;

/**
 * The count element: the number for sight, and ", N items" for screen readers, so the basket link's accessible name carries the count (the
 * link's own word, "Cart", is visually hidden text in the header, not an aria-label, which would override this). Empty when the basket is.
 */
function cart_count_markup(int $count): string
{
    if ($count < 1) {
        return '<span class="pkde-cart-count"></span>';
    }

    /* translators: %d: the number of items in the basket, read after the basket link's own name ("Cart, 2 items"). */
    $items = sprintf(_n('%d item', '%d items', $count, 'sage'), $count);

    return '<span class="pkde-cart-count"><span aria-hidden="true">' . $count . '</span><span class="sr-only">, ' . esc_html($items) . '</span></span>';
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
