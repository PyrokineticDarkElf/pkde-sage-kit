<?php

/**
 * The standard shop archive (from tcs, MGR-77): one markup and one stylesheet for every site, changed through hooks and --pkde-shop-* variables,
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

it('styles the archive with kit tokens and --pkde-shop-* variables only', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $section = substr($css, strpos($css, "/* A term's description"), strpos($css, '/* Product gallery') - strpos($css, "/* A term's description"));

    expect($css)->toContain('.shop-archive__results ul.products', '.shop-archive__filters-title', '.pkde-term-description', '--pkde-shop-line', '--pkde-shop-accent', '--pkde-shop-filters-width')
        ->and($css)->not->toContain('--wp--preset')
        ->and($section)->not->toMatch('/#[0-9a-f]{3,8}\b/i');
});

it('documents every shop hook and variable it defines', function () use ($root) {
    $doc = file_get_contents("$root/docs/woocommerce-templates.md");

    foreach (['pkde_kit/shop_term_toggle', 'pkde_kit/shop_filters_title', 'pkde_kit/shop_before_results', 'pkde_kit/shop_after_results', 'pkde_kit/shop_page_header', '--pkde-shop-line', '--pkde-shop-accent', '--pkde-shop-muted', '--pkde-shop-ink', '--pkde-shop-surface', '--pkde-shop-radius', '--pkde-shop-panel-radius', '--pkde-shop-gap', '--pkde-shop-filters-width', '--pkde-shop-sticky-top'] as $name) {
        expect($doc)->toContain($name);
    }
});
