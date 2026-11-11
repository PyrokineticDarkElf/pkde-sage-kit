<?php

// Recently viewed products (app/woocommerce/recently-viewed.php, TPL-134): the pure parts need no WordPress; the markup and the cache-safety are
// checked as files.
require_once dirname(__DIR__, 2) . '/app/woocommerce/recently-viewed.php';

use function App\recent_batch;
use function App\recent_ids;
use function App\recent_merge;

$root = dirname(__DIR__, 2);

it('reads ids from a request or a stored value: positive integers, once each, in order, capped', function () {
    expect(recent_ids('5, 9,12'))->toBe([5, 9, 12])
        ->and(recent_ids([5, '9', 5, 0, -3, 'x', '7;DROP', 1.5]))->toBe([5, 9])
        ->and(recent_ids('1,2,3,4,5,6', 4))->toBe([1, 2, 3, 4])
        ->and(recent_ids(null))->toBe([])
        ->and(recent_ids(['a' => 3, 'b' => 4]))->toBe([3, 4])
        ->and(recent_ids(str_repeat('9', 20)))->toBe([]);
});

it('merges the browser\'s list into the account\'s: newest first, each product once, capped', function () {
    expect(recent_merge([3, 1], [1, 2, 4]))->toBe([3, 1, 2, 4])
        ->and(recent_merge([3, 1], [1, 2, 4], 3))->toBe([3, 1, 2])
        ->and(recent_merge([], []))->toBe([]);
});

it('leaves the viewed product out and returns the batch with an honest total', function () {
    expect(recent_batch([5, 6, 7, 8, 9, 10, 11], 6, 0, 5))->toBe(['ids' => [5, 7, 8, 9, 10], 'total' => 6])
        ->and(recent_batch([5, 6, 7, 8, 9, 10, 11], 6, 5, 5))->toBe(['ids' => [11], 'total' => 6])
        ->and(recent_batch([5], 5, 0, 5))->toBe(['ids' => [], 'total' => 0]);
});

it('keeps the page the same for every visitor: an empty marker and an empty hidden box, no visitor data in the code', function () use ($root) {
    $php = file_get_contents("$root/app/woocommerce/recently-viewed.php");
    $box = file_get_contents("$root/resources/views/partials/recently-viewed.blade.php");

    expect($box)->toContain('data-pkde-recent', 'hidden', 'aria-labelledby=', 'aria-live="polite"', 'type="button"')
        ->and($box)->not->toContain('get_user', 'wc_get_product', 'WC()->')
        ->and($php)->toContain('data-pkde-recent-view="%d" data-sync-url="%s"', 'pkde_kit/recently_viewed', 'nocache_headers()')
        // the sync endpoint is POST-only and same-origin only, and stores ids and nothing else
        ->and($php)->toContain("REQUEST_METHOD'] ?? '') !== 'POST'", 'HTTP_SEC_FETCH_SITE', "update_user_meta(get_current_user_id(), '_pkde_recently_viewed'")
        ->and($php)->not->toContain('setcookie', 'wp_create_nonce')
        ->and(file_get_contents("$root/resources/views/partials/sidebar-shop.blade.php"))->toContain('\App\recently_viewed_box()')
        ->and(file_get_contents("$root/resources/js/kit.js"))->toContain('recently-viewed.js')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('recently-viewed.css')
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain('woocommerce/recently-viewed.php');
});

it('is off until a site opts in, and the list lives in the browser (no cookie, no third party)', function () use ($root) {
    $php = file_get_contents("$root/app/woocommerce/recently-viewed.php");
    $js = file_get_contents("$root/resources/js/recently-viewed.js");

    expect($php)->toContain("apply_filters('pkde_kit/recently_viewed', false)")
        ->and($js)->toContain('localStorage')->not->toContain('document.cookie');
});

it('gives the Cart and Checkout blocks the shortcode pages\' look from the same tokens (TPL-131)', function () use ($root) {
    $css = file_get_contents("$root/resources/css/cart-checkout-blocks.css");

    expect($css)->toContain('.wc-block-cart__submit-button', '.wc-block-components-checkout-place-order-button', 'var(--color-brand-600)', 'var(--color-brand-600-fg)', 'border-radius: 999px', 'outline: 2px solid var(--color-brand-600)')
        ->and($css)->not->toMatch('/#[0-9a-f]{3,6}\b/i')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('cart-checkout-blocks.css');
});
