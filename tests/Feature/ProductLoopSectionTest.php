<?php

/**
 *U-1: the Product Loop block takes over from Recent Products and Best Selling Products: it gains the section header, "View all" link, section theme
 * and card style those two had, and the two are hidden from the inserter (still registered, still rendering what pages already hold) for one release before they go.
 * The "View all" link of the Brands and Categories blocks no longer points at the page it is on (U-2/U-3: those pages are ordinary pages holding the blocks).
 */
$root = dirname(__DIR__, 2);

it('gives the Product Loop block a section heading, description, "View all" link, theme and card style, all off by default', function () use ($root) {
    $block = json_decode(file_get_contents("$root/resources/blocks/product-loop/block.json"), true);
    $render = file_get_contents("$root/resources/blocks/product-loop/render.php");
    $edit = file_get_contents("$root/resources/blocks/product-loop/edit.jsx");

    expect($block['attributes']['heading']['default'])->toBe('')
        ->and($block['attributes']['description']['default'])->toBe('')
        ->and($block['attributes']['viewAll']['default'])->toBeFalse()
        ->and($block['attributes']['theme']['default'])->toBe('')
        ->and($block['attributes']['cardStyle']['default'])->toBe('white')
        // the same shared pieces as Recent / Best Selling / Featured, so the look is one look
        ->and($render)->toContain("pkde-kit::partials.blocks.section-header", '\\App\\pkde_loop_theme_classes($theme)', '\\App\\pkde_loop_theme_content_open($theme)', '\\App\\pkde_sanitize_card_style(')
        // the card style bridge is set for the shortcode's cards and reset after, so it cannot leak into a later block
        ->and($render)->toContain("\$GLOBALS['pkde_product_card_style'] = \$card_style;", "\$GLOBALS['pkde_product_card_style'] = null;")
        ->and(strpos($render, "= \$card_style;"))->toBeLessThan(strpos($render, 'do_shortcode($shortcode'))
        ->and(strpos($render, 'do_shortcode($shortcode'))->toBeLessThan(strpos($render, "= null;"))
        ->and($edit)->toContain('heading', 'viewAll');
});

it('hides Recent Products and Best Selling Products from the inserter but keeps them registered', function () use ($root) {
    foreach (['recent-products', 'best-selling-products'] as $slug) {
        $block = json_decode(file_get_contents("$root/resources/blocks/$slug/block.json"), true);

        expect($block['supports']['inserter'])->toBeFalse()
            ->and($block['description'])->toStartWith('Deprecated')
            ->and(is_file("$root/resources/blocks/$slug/render.php"))->toBeTrue()
            ->and(file_get_contents("$root/app/blocks.php"))->toContain("'$slug'"); // still registered, so existing pages keep rendering
    }
});

it('leaves the "View all" link off the directory page the Brands and Categories blocks sit on', function () use ($root) {
    foreach (['brands' => 'brands_page', 'product-categories' => 'categories_page'] as $slug => $var) {
        expect(file_get_contents("$root/resources/blocks/$slug/render.php"))->toContain('$' . $var . ' && ! is_page($' . $var . '->ID) ? get_permalink($' . $var . ") : ''");
    }
});
