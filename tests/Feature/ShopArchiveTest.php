<?php

/**
 * The standard shop archive: one markup and one stylesheet for every site, changed through hooks and --pkde-shop-* variables,
 * never copied. These checks read the files, so a rename or a stray site colour fails here.
 */
$root = dirname(__DIR__, 2);

it('prints the BEM archive without a woocommerce class on the list, and fires the documented hooks', function () use ($root) {
    $body = file_get_contents("$root/resources/views/partials/shop-archive-body.blade.php");
    $archive = file_get_contents("$root/resources/views/partials/shop-archive.blade.php");
    $page = file_get_contents("$root/resources/views/partials/shop-page.blade.php");

    expect($body)->toContain('shop-archive__filters-title', 'shop-archive__filters', 'shop-archive__results', "'pkde_kit/shop_filters_title'", "'pkde_kit/shop_before_results'", "'pkde_kit/shop_after_results'")
        ->and($body)->not->toMatch('/class="[^"]*\bwoocommerce\b(?!-)/')
        ->and($archive)->toContain("'pkde_kit/shop_term_toggle'", 'term_toggle(', 'print_term_toggle')
        ->and($page)->toContain("'pkde_kit/shop_page_header'", 'shop-archive');
});

it('documents every shop hook and variable it defines', function () use ($root) {
    $doc = file_get_contents("$root/docs/woocommerce-templates.md");

    foreach (['pkde_kit/shop_term_toggle', 'pkde_kit/shop_filters_title', 'pkde_kit/shop_before_results', 'pkde_kit/shop_after_results', 'pkde_kit/shop_page_header', '--pkde-shop-line', '--pkde-shop-accent', '--pkde-shop-muted', '--pkde-shop-ink', '--pkde-shop-surface', '--pkde-shop-radius', '--pkde-shop-panel-radius', '--pkde-shop-gap', '--pkde-shop-filters-width', '--pkde-shop-sticky-top', '--pkde-shop-heading-weight', '--pkde-shop-columns', '--pkde-shop-columns-tablet', '--pkde-shop-columns-phone', '--pkde-shop-pad-top'] as $name) {
        expect($doc)->toContain($name);
    }
});

it('puts the card\'s star rating where the site wants it in DOM order (default above the button), with no need for CSS order', function () use ($root) {
    require_once "$root/app/woocommerce/card-rating.php";

    expect(App\normalize_card_rating_position('above_title'))->toBe('above_title')
        ->and(App\normalize_card_rating_position('below_title'))->toBe('below_title')
        ->and(App\normalize_card_rating_position('above_button'))->toBe('above_button')
        ->and(App\normalize_card_rating_position('left'))->toBe('above_button')
        ->and(App\normalize_card_rating_position(null))->toBe('above_button')
        ->and(App\CARD_RATING_POSITIONS)->toBe(['above_button', 'below_title', 'above_title']);

    $card = file_get_contents("$root/resources/views/woocommerce/content-product.blade.php");
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $positionOf = fn(string $needle) => strpos($card, $needle);

    expect($card)->toContain('\App\card_rating_position()')
        ->and($positionOf("=== 'above_title'"))->toBeLessThan($positionOf('<h2'))
        ->and($positionOf("=== 'below_title'"))->toBeGreaterThan($positionOf('<h2'))->toBeLessThan($positionOf('get_price_html'))
        ->and($positionOf("=== 'above_button'"))->toBeGreaterThan($positionOf('get_price_html'))->toBeLessThan($positionOf('pkde-product-actions'))
        ->and($css)->toContain('.pkde-product-card__rating--above-button {', '.pkde-product-card__rating--above-button + .pkde-product-actions {')
        ->and(file_get_contents("$root/app/woocommerce.php"))->toContain('woocommerce/card-rating.php')
        ->and(file_get_contents("$root/docs/woocommerce-templates.md"))->toContain('pkde_kit/card_rating_position', 'above_button', 'below_title', 'above_title');
});
