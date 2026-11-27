<?php

// The Sale/New badges (app/woocommerce/badges.php): the pure parts need no WordPress.
require_once dirname(__DIR__, 2) . '/app/woocommerce/badges.php';

use function App\badge_items;

$root = dirname(__DIR__, 2);

it('shows Sale and New from the product\'s facts, linked on archives and plain on a curated row', function () {
    $linked = badge_items(true, true, 'Wall clock', 'https://example.test/shop/?sage_view=sale', 'https://example.test/shop/?sage_view=new', true);

    expect(array_column($linked, 'key'))->toBe(['sale', 'new'])
        ->and($linked[0]['url'])->toBe('https://example.test/shop/?sage_view=sale')
        ->and($linked[0]['aria'])->toBe('Sale: Wall clock (see all products on sale)')
        ->and($linked[1]['aria'])->toBe('New: Wall clock (see the newest products)');

    $plain = badge_items(false, true, 'Wall clock', 's', 'n', false);

    expect(array_column($plain, 'key'))->toBe(['new'])->and($plain[0]['url'])->toBe('')
        ->and(badge_items(false, false, 'x', 's', 'n', true))->toBe([]);
});

