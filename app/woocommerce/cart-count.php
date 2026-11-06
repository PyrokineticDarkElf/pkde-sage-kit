<?php

/**
 * The header's basket count, kept live (owner, TPL-147). The site's header prints cart_count_html() inside its badge, and the badge carries
 * `data-pkde-cart-badge`. The count is registered as a WooCommerce cart fragment, so WooCommerce's own add-to-cart script swaps in the new number
 * after every AJAX add to basket (product cards, related products); resources/js/cart-count.js does the same after a change on the basket page,
 * which WooCommerce's cart script does not. The count is empty at 0 and the badge hides itself then (shop-pages.css), so it appears at the
 * first add without a reload. The header names its link with visually hidden text (not aria-label), so the count is part of the link's
 * accessible name: "Cart" when empty, "Cart, 2 items" (TPL-148).
 *
 * Cache-safe (MGR-76): the page always prints the EMPTY count; the browser fills it in (resources/js/cart-badge.js), so the page cache can keep serving
 * visitors who have a basket. See docs/page-cache.md.
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

/**
 * The count element for a PAGE: always the EMPTY one, the same for every visitor, so a page the page cache stores never carries anyone's basket
 * (MGR-76). The number is filled in by the browser (resources/js/cart-badge.js) from the basket cookie and WooCommerce's fragments, and by
 * WooCommerce's add-to-cart script after an AJAX add. Only the fragment (an AJAX answer, never cached) passes $live = true for the visitor's real count.
 */
function cart_count_html(bool $live = false): string
{
    if (! $live) {
        return cart_count_markup(0);
    }

    return cart_count_markup(function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0);
}

if (! function_exists('add_filter')) {
    return;
}

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $fragments['span.pkde-cart-count'] = cart_count_html(true);

    return $fragments;
});
