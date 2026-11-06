<?php

// app/woocommerce/cart-count.php defines pure functions and returns early outside WordPress, so it loads here without WordPress.
if (! function_exists('_n')) {
    function _n($single, $plural, $number, $domain = null)
    {
        return $number === 1 ? $single : $plural;
    }
}
// The same stubs as ShopViewToggleTest, under the same guard, so either file can load first.
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
require_once dirname(__DIR__, 2) . '/app/woocommerce/cart-count.php';

use function App\cart_count_html;
use function App\cart_count_markup;

it('prints the number for sight and ", N items" for screen readers, and nothing at 0 so the badge hides itself', function () {
    expect(cart_count_markup(3))->toBe('<span class="pkde-cart-count"><span aria-hidden="true">3</span><span class="sr-only">, 3 items</span></span>')
        ->and(cart_count_markup(1))->toContain('<span class="sr-only">, 1 item</span>')
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

// MGR-76: the page always prints the EMPTY count; only the fragment (an AJAX answer) carries the visitor's number.
it('prints an empty count on a page for every visitor, and the real number only in the live fragment', function () {
    if (! function_exists('WC')) {
        function WC()
        {
            return (object) ['cart' => new class {
                public function get_cart_contents_count(): int
                {
                    return 4;
                }
            }];
        }
    }

    expect(cart_count_html())->toBe('<span class="pkde-cart-count"></span>')
        ->and(cart_count_html(false))->toBe('<span class="pkde-cart-count"></span>')
        ->and(cart_count_html(true))->toContain('<span aria-hidden="true">4</span>')
        ->and(file_get_contents(dirname(__DIR__, 2) . '/app/woocommerce/cart-count.php'))->toContain("\$fragments['span.pkde-cart-count'] = cart_count_html(true);");
});
