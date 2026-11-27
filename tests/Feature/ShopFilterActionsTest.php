<?php

/**
 * "Apply filters" and "Reset" on the shop filter form: a tick only changes the form, the choices are sent once, and Reset goes back to the archive
 * without them. The pure parts run here; the markup, the script and the stylesheet are read as files so a stray auto-submit or a missing hook fails.
 */
require_once dirname(__DIR__, 2) . '/app/ShopFilters.php';

use App\ShopFilters;

$root = dirname(__DIR__, 2);

it('reads a filter choice or a price bound as applied, and nothing else', function () {
    expect(ShopFilters::hasAppliedFilters(['sage_filter_product_cat' => ['clocks']]))->toBeTrue()
        ->and(ShopFilters::hasAppliedFilters(['min_price' => '10']))->toBeTrue()
        ->and(ShopFilters::hasAppliedFilters(['max_price' => '0']))->toBeTrue()
        ->and(ShopFilters::hasAppliedFilters(['orderby' => 'price', 'paged' => '2', 'sage_view' => 'sale']))->toBeFalse()
        ->and(ShopFilters::hasAppliedFilters(['sage_filter_product_cat' => [], 'min_price' => '']))->toBeFalse()
        ->and(ShopFilters::hasAppliedFilters([]))->toBeFalse();
});

it('resets to the archive without the form\'s choices and the page number, and keeps everything else', function () {
    $base = 'https://shop.example/category/clocks/';

    expect(ShopFilters::resetUrl($base, ['sage_filter_product_cat' => ['a', 'b'], 'sage_filter_pa_colour' => ['red'], 'min_price' => '5', 'max_price' => '20', 'paged' => '3']))->toBe($base)
        ->and(ShopFilters::resetUrl($base, ['orderby' => 'price', 'sage_filter_product_cat' => ['a'], 'paged' => '2']))->toBe($base . '?orderby=price')
        ->and(ShopFilters::resetUrl($base, ['sage_view' => 'sale', 'min_price' => '1']))->toBe($base . '?sage_view=sale')
        ->and(ShopFilters::resetUrl('https://shop.example/?post_type=product', ['orderby' => 'date', 'min_price' => '1']))->toBe('https://shop.example/?post_type=product&orderby=date')
        ->and(ShopFilters::resetUrl($base, []))->toBe($base);
});

it('does not send a request per tick: the change handler no longer submits the form', function () use ($root) {
    $kit = file_get_contents("$root/resources/js/kit.js");
    $own = file_get_contents("$root/resources/js/shop-filters.js");

    expect($kit)->not->toContain('requestSubmit')
        ->and($kit)->toContain("import './shop-filters.js'")
        ->and($kit)->toContain('data-price-input') // empty prices still drop their name on submit
        ->and($own)->not->toContain('requestSubmit')
        ->and($own)->not->toContain('.submit(')
        ->and($own)->not->toContain('preventDefault'); // nothing in the script can block a submit
});

it('prints a plain submit button, a reset that works without JavaScript, and a status line for screen readers', function () use ($root) {
    $view = file_get_contents("$root/resources/views/partials/sidebar-shop.blade.php");

    expect($view)->toContain('type="submit" class="pkde-filter-apply"')
        ->and($view)->toContain('data-shop-apply')
        ->and($view)->toContain("'Apply filters', 'sage'")
        ->and($view)->toContain("'Apply filters (%d)', 'sage'")
        ->and($view)->toContain('hasAppliedFilters')
        ->and($view)->toContain('ShopFilters::resetUrl')
        ->and($view)->toContain('type="reset"')
        ->and($view)->toContain("'Reset filters', 'sage'")
        ->and($view)->toContain('role="status" aria-live="polite" data-shop-status');
});

it('styles the buttons from theme tokens, with a focus ring, and never disables the quiet state', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-filter-actions.css");

    expect($css)->toContain('--pkde-filter-apply-bg', 'var(--color-brand-600)', 'var(--color-brand-800)')
        ->and($css)->toContain(':focus-visible')
        ->and($css)->toContain('position: sticky')
        ->and($css)->toContain('[data-pending="0"]')
        ->and($css)->not->toContain('pointer-events')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./shop-filter-actions.css"');
});

it('keeps Apply and Reset reachable on a desktop: only the filter groups scroll inside the floating column, the bar stays out of the scroller (2026.11.18.1)', function () use ($root) {
    $pages = preg_replace('~/\*.*?\*/~s', '', file_get_contents("$root/resources/css/shop-pages.css")); // comments may mention the properties
    $bar = file_get_contents("$root/resources/css/shop-filter-actions.css");
    $view = file_get_contents("$root/resources/views/partials/sidebar-shop.blade.php");

    // the floating column is capped to the height left under the sticky header, and the groups wrapper (not the column, not the bar) is the scroller
    expect($pages)->toMatch('/@media \(min-width: 1024px\) and \(min-height: 820px\) \{\s*\.shop-archive__filters \{[^}]*max-height: calc\(100vh - var\(--pkde-shop-sticky-top, 5\.5rem\) - 1rem\)/s')
        ->and($pages)->toContain('100dvh')
        ->and($pages)->toMatch('/\.shop-archive__filters \.pkde-filter-groups \{[^}]*overflow-y: auto[^}]*scrollbar-width: thin/s')
        ->and($pages)->toMatch('/\.shop-archive__filters #shop-filters > form \{|\#shop-filters > form/')
        ->and($pages)->not->toMatch('/\.shop-archive__filters \{[^}]*overflow-y: auto/s'); // the column itself never scrolls: the bar sticks to the viewport

    // the bar is sticky at every width (no static override from lg up) and the groups sit in their own wrapper in the form
    expect($bar)->toContain('position: sticky')
        ->and($bar)->not->toContain('position: static')
        ->and($view)->toContain('class="pkde-filter-groups space-y-4" data-shop-groups');
});
