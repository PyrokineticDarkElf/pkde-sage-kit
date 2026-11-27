<?php

// app/woocommerce/view-toggle.php defines pure functions and returns early outside WordPress, so it loads here without WordPress.
if (! function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
    function __($text, $domain = null)
    {
        return $text;
    }
}
require_once dirname(__DIR__, 2) . '/app/woocommerce/view-toggle.php';

use function App\shop_view_toggle_html;

it('prints two plain buttons with aria-pressed and names for assistive technology, the same for every visitor', function () {
    $html = shop_view_toggle_html();

    expect($html)->toContain('role="group"')
        ->and($html)->toContain('aria-label="Product view"')
        ->and(substr_count($html, '<button type="button"'))->toBe(2)
        ->and($html)->toContain('data-pkde-view-set="grid" aria-pressed="true"')
        ->and($html)->toContain('data-pkde-view-set="list" aria-pressed="false"')
        ->and($html)->toContain('<span class="sr-only">Grid view</span>')
        ->and($html)->toContain('<span class="sr-only">List view</span>')
        ->and($html)->toContain('aria-hidden="true"');
});

it('turns View basket into an icon button the height of the add-to-cart button, named View basket, with a focus ring', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');
    $card = file_get_contents(dirname(__DIR__, 2) . '/resources/views/woocommerce/content-product.blade.php');

    expect($css)->toContain('.pkde-product-actions > a.added_to_cart {')
        ->and($css)->toContain('font-size: 0;')
        ->and($css)->toContain('.pkde-product-actions > a.added_to_cart::before')
        ->and($css)->toContain('.pkde-product-actions > a.added_to_cart:focus-visible')
        ->and($css)->toContain('flex: 0 0 2.5rem;')
        // the short description goes through the one card helper: tags stripped, entities decoded once (CardTextTest), a server cap, then the CSS clamp
        ->and($card)->toContain('\\App\\card_text((string) $product->get_short_description())')
        ->and($card)->not->toContain('wp_trim_words');
});
