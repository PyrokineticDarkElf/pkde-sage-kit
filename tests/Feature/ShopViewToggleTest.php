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

it('saves the choice in the browser only, never in the HTML, and applies it before the first paint', function () {
    $root = dirname(__DIR__, 2);
    $php = file_get_contents("$root/app/woocommerce/view-toggle.php");
    $js = file_get_contents("$root/resources/js/shop-view.js");

    expect($php)->toContain("localStorage.getItem('pkde_shop_view')")
        ->and($php)->toContain("add_action('wp_head'")
        ->and($php)->toContain("apply_filters('pkde_kit/shop_view_toggle', true)")
        ->and($js)->toContain("localStorage.setItem(KEY, view)")
        ->and($js)->toContain('catch')
        ->and(file_get_contents("$root/resources/js/kit.js"))->toContain("import './shop-view.js';")
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain("woocommerce/view-toggle.php");
});

it('draws the list card from the same markup: image left, details and the action row right, stacked on a phone, grid the default', function () {
    $root = dirname(__DIR__, 2);
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $card = file_get_contents("$root/resources/views/woocommerce/content-product.blade.php");

    expect($css)->toContain('html[data-pkde-view="list"] .woocommerce ul.products li.product.pkde-product-card')
        ->and($css)->toContain('.pkde-product-card__excerpt {')
        ->and($css)->toContain('@media (min-width: 40rem)')
        ->and($css)->toContain('.pkde-view-toggle__button[aria-pressed="true"]')
        ->and($card)->toContain('pkde-product-card__media')
        ->and($card)->toContain('pkde-product-card__excerpt')
        ->and($card)->toContain('pkde-product-actions pkde-product-actions--default');
});
