<?php

// app/woocommerce/cart-count.php defines pure functions and returns early outside WordPress, so it loads here without WordPress.
require_once dirname(__DIR__, 2) . '/app/woocommerce/cart-count.php';

use function App\cart_count_markup;

it('prints the number of items, and nothing at 0 so the badge hides itself', function () {
    expect(cart_count_markup(3))->toBe('<span class="pkde-cart-count">3</span>')
        ->and(cart_count_markup(0))->toBe('<span class="pkde-cart-count"></span>');
});

it('registers the count as a cart fragment, hides an empty badge in CSS and refreshes it after basket-page changes', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents("$root/app/woocommerce/cart-count.php"))->toContain("\$fragments['span.pkde-cart-count']")
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain("woocommerce/cart-count.php")
        ->and(file_get_contents("$root/resources/css/shop-pages.css"))->toContain('[data-pkde-cart-badge]:has(> .pkde-cart-count:empty)')
        ->and(file_get_contents("$root/resources/js/cart-count.js"))->toContain("'updated_cart_totals wc_cart_emptied'")
        ->and(file_get_contents("$root/resources/js/cart-count.js"))->toContain("'span.pkde-cart-count'")
        ->and(file_get_contents("$root/resources/js/kit.js"))->toContain("import './cart-count.js';");
});
