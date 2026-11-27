<?php

// The product page's sections (app/woocommerce/product-sections.php) and add-to-basket feedback (app/woocommerce/add-to-basket.php). The pure
// parts need no WordPress; the markup and CSS are checked as files.
require_once dirname(__DIR__, 2) . '/app/woocommerce/product-sections.php';
require_once dirname(__DIR__, 2) . '/app/woocommerce/add-to-basket.php';

use function App\add_to_basket_attributes;
use function App\add_to_basket_strings;
use function App\product_section_default_count;
use function App\product_section_default_title;
use function App\product_section_ids;
use function App\product_section_keys;

$root = dirname(__DIR__, 2);

it('prints the sections in the configured order, only the known ones, each once', function () {
    expect(product_section_keys(['upsells', 'cross-sells', 'related']))->toBe(['upsells', 'cross-sells', 'related'])
        ->and(product_section_keys(['related', 'upsells']))->toBe(['related', 'upsells'])
        ->and(product_section_keys(['related', 'related', 'nope', 3, 'cross-sells']))->toBe(['related', 'cross-sells'])
        ->and(product_section_keys([]))->toBe([]);
});

it('names the sections as the owner asked, and falls back without a category', function () {
    expect(product_section_default_title('upsells'))->toBe('You may also like')
        ->and(product_section_default_title('cross-sells'))->toBe('Goes well with')
        ->and(product_section_default_title('related', 'Wall Clocks'))->toBe('More from Wall Clocks')
        ->and(product_section_default_title('related'))->toBe('Related products')
        ->and([product_section_default_count('upsells'), product_section_default_count('cross-sells'), product_section_default_count('related')])->toBe([8, 8, 12]);
});

it('never repeats a product in a later section, and a count of 0 switches a section off', function () {
    expect(product_section_ids([5, 6, 7, 6], [7], 10))->toBe([5, 6])
        ->and(product_section_ids([5, 6, 7], [7], 2))->toBe([5, 6])
        ->and(product_section_ids([5, 6, 7], [7], 10, false))->toBe([5, 6, 7])
        ->and(product_section_ids([5, 6], [], 0))->toBe([])
        ->and(product_section_ids(['5', '6'], [], 5))->toBe([5, 6]);
});

it('prints one status region with the words and the endpoints, the same for every visitor', function () {
    $attributes = add_to_basket_attributes(add_to_basket_strings(), 'https://example.test/basket/', true, 'https://example.test/?wc-ajax=add_to_cart');

    expect($attributes)->toHaveKeys(['data-pkde-atc-status', 'data-adding', 'data-added', 'data-added-label', 'data-error', 'data-view-label', 'data-view-url', 'data-ajax-single', 'data-ajax-url'])
        ->and($attributes['data-added'])->toContain('%s')
        ->and($attributes['data-ajax-single'])->toBe('1')
        ->and(add_to_basket_attributes(add_to_basket_strings(), '', false, '')['data-ajax-single'])->toBe('0');
});

it('replaces WooCommerce\'s upsells and related output and keeps the basket\'s own cross-sells', function () use ($root) {
    $php = file_get_contents("$root/app/woocommerce/product-sections.php");

    expect($php)->toContain("remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15)", "remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20)")
        ->and($php)->not->toContain('woocommerce_cross_sell_display')
        ->and($php)->toContain('pkde_kit/product_sections_enabled', 'pkde_kit/product_sections', 'pkde_kit/product_section_title', 'pkde_kit/product_section_count', 'pkde_kit/product_section_order', 'pkde_kit/related_load_more')
        // the site's own card, lazy images, no per-visitor input anywhere in the section
        ->and($php)->toContain("wc_get_template_part('content', 'product')", "'loading' => 'lazy'")
        ->and($php)->not->toContain('get_current_user_id', 'WC()->cart', 'WC()->session');
    expect(file_get_contents("$root/app/woocommerce.php"))->toContain('woocommerce/product-sections.php', 'woocommerce/add-to-basket.php');
});

