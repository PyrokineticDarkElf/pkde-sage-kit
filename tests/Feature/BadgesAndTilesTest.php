<?php

// The Sale/New badges (app/woocommerce/badges.php, MGR-77) and the category tile image helper (app/tile-image.php, TCS-84): the pure parts need no WordPress.
require_once dirname(__DIR__, 2) . '/app/woocommerce/badges.php';
require_once dirname(__DIR__, 2) . '/app/tile-image.php';

use function App\badge_items;
use function App\upload_original_url;

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

it('finds the original of a resized upload and leaves other URLs alone', function () {
    expect(upload_original_url('https://example.test/app/uploads/2026/10/photo-768x512.jpeg'))->toBe('https://example.test/app/uploads/2026/10/photo.jpeg')
        ->and(upload_original_url('https://example.test/app/uploads/photo.jpeg'))->toBe('https://example.test/app/uploads/photo.jpeg')
        ->and(upload_original_url('https://example.test/a-1x1-b.png'))->toBe('https://example.test/a-1x1-b.png');
});

it('makes the New rule filterable (counts and days) and keeps one partial, one look and no site colour', function () use ($root) {
    $recent = file_get_contents("$root/app/RecentContent.php");
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $card = file_get_contents("$root/resources/views/woocommerce/content-product.blade.php");
    $badgesCss = substr($css, strpos($css, '/* Sale and New badges'));

    expect($recent)->toContain("'pkde_kit/recent_counts'", "'pkde_kit/new_badge_days'")
        ->and($card)->toContain("partials.product-badges")->not->toContain('bg-neutral-950 px-3 py-1')
        ->and(file_get_contents("$root/app/woocommerce/badges.php"))->toContain("'pkde_kit/product_badges'")
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain('woocommerce/badges.php')
        ->and($badgesCss)->toContain('--pkde-badge-sale-bg', '--pkde-badge-sale-fg', '--pkde-badge-new-bg', '--pkde-badge-new-fg', '--pkde-badge-radius')
        ->and($badgesCss)->not->toContain('--wp--preset')->not->toMatch('/#[0-9a-f]{3,8}\b/i')
        ->and(file_get_contents("$root/docs/woocommerce-templates.md"))->toContain('--pkde-badge-sale-bg', 'pkde_kit/new_badge_days', 'category_tile_image');
});
