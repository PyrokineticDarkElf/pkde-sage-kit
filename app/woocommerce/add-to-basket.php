<?php

/**
 * Add-to-basket feedback (MGR-79). On a fast, cached site the click on "Add to basket" used to look dead for a second or two and people clicked
 * again. resources/js/add-to-basket.js now gives every add-to-basket button a busy state (aria-busy, no second click, a spinner), then an
 * "Added" state with WooCommerce's "View basket" link, and says both in a polite live region. This file prints that region and the strings.
 *
 * The region is the same for every visitor (static text and the basket URL), so the page cache is unaffected. It carries:
 *   data-adding / data-added ("%s added to basket.") / data-added-label ("Added") / data-error: the words
 *   data-view-label / data-view-url: the "View basket" link added after a single product's button
 *   data-ajax-single / data-ajax-url: AJAX add to cart for simple products on the product page (below)
 *
 * AJAX add to cart on the single product page (simple products only, the plain quantity + button form; variable, grouped and add-on forms submit
 * normally). On when the shop's own setting "Enable AJAX add to cart buttons on archives" is on and "Redirect to the basket page after
 * successful addition" is off; `pkde_kit/ajax_add_to_cart_single` (bool, default true) turns it off. If the request fails the form is submitted
 * the ordinary way, so nothing is ever lost.
 */

namespace App;

/**
 * The words of the add-to-basket states (pure; translated when WordPress is there).
 *
 * @return array{adding: string, added: string, addedLabel: string, error: string, viewLabel: string}
 */
function add_to_basket_strings(): array
{
    $t = fn(string $text) => function_exists('__') ? __($text, 'sage') : $text;

    return [
        'adding' => $t('Adding to basket…'),
        /* translators: %s: the product name */
        'added' => $t('%s added to basket.'),
        'addedLabel' => $t('Added'),
        'error' => $t('Sorry, this could not be added to your basket. Please try again.'),
        'viewLabel' => $t('View basket'),
    ];
}

/** The region's attributes (pure), so a test can check them without WordPress. @return array<string, string> */
function add_to_basket_attributes(array $strings, string $viewUrl, bool $ajaxSingle, string $ajaxUrl): array
{
    return [
        'data-pkde-atc-status' => '',
        'data-adding' => $strings['adding'],
        'data-added' => $strings['added'],
        'data-added-label' => $strings['addedLabel'],
        'data-error' => $strings['error'],
        'data-view-label' => $strings['viewLabel'],
        'data-view-url' => $viewUrl,
        'data-ajax-single' => $ajaxSingle ? '1' : '0',
        'data-ajax-url' => $ajaxUrl,
    ];
}

if (! function_exists('add_action') || ! class_exists('WooCommerce')) {
    return;
}

/** Whether the product page adds simple products without a reload. */
function ajax_add_to_cart_single_enabled(): bool
{
    return get_option('woocommerce_enable_ajax_add_to_cart') === 'yes'
        && get_option('woocommerce_cart_redirect_after_add') !== 'yes'
        && (bool) apply_filters('pkde_kit/ajax_add_to_cart_single', true);
}

add_action('wp_footer', function () {
    if (is_admin() || is_cart() || is_checkout()) {
        return;
    }

    $attributes = add_to_basket_attributes(add_to_basket_strings(), wc_get_cart_url(), ajax_add_to_cart_single_enabled(), \WC_AJAX::get_endpoint('add_to_cart'));
    $html = '';

    foreach ($attributes as $name => $value) {
        $html .= $value === '' ? ' ' . $name : sprintf(' %s="%s"', $name, esc_attr($value));
    }

    echo '<div class="sr-only" role="status" aria-live="polite" aria-atomic="true"' . $html . '></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}, 5);
