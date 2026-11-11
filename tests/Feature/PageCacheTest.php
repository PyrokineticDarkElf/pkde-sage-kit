<?php

// app/woocommerce/page-cache.php: a page that prints something personal is marked uncacheable (MGR-76).
require_once dirname(__DIR__, 2) . '/app/woocommerce/page-cache.php';

use function App\no_page_cache;

it('marks a response uncacheable with DONOTCACHEPAGE', function () {
    expect(defined('DONOTCACHEPAGE'))->toBeFalse();

    no_page_cache();

    expect(defined('DONOTCACHEPAGE') && DONOTCACHEPAGE)->toBeTrue();
});

it('prints notices through one function that marks the page uncacheable only when there is a notice', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents("$root/app/woocommerce/page-cache.php"))->toContain('wc_notice_count() > 0', 'no_page_cache();', 'wc_print_notices();')
        ->and(file_get_contents("$root/resources/views/partials/woocommerce-notices.blade.php"))->toContain('print_notices_uncached()')->not->toContain('@php(wc_print_notices())')
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain('woocommerce/page-cache.php');
});

it('keeps no per-visitor call in the kit\'s views, blocks and app code apart from the live fragment and the notices helper', function () {
    $root = dirname(__DIR__, 2);
    $hits = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        $path = $file->getPathname();

        if (! preg_match('/\.(php|js)$/', $path) || preg_match('#/(vendor|node_modules|tests|\.git)/#', $path)) {
            continue;
        }

        if (preg_match_all('/WC\(\)->(cart|session|customer)|get_cart_contents_count|wp_get_current_user|get_current_user_id|\$_COOKIE|wp_get_current_commenter|comment_form\(/', file_get_contents($path), $found)) {
            $hits[str_replace("$root/", '', $path)] = array_unique($found[0]);
        }
    }

    // The ONLY allowed readers: the live cart fragment (an AJAX answer), the REST cart loader of the product-loop block and the recently viewed
    // sync endpoint (a POST answer that stores a logged-in visitor's list; the page only carries an empty placeholder).
    expect(array_keys($hits))->toEqualCanonicalizing(['app/woocommerce/cart-count.php', 'resources/blocks/product-loop/render.php', 'app/woocommerce/recently-viewed.php']);
});
