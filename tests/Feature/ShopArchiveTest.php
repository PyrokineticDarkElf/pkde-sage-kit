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

    foreach (['pkde_kit/shop_term_toggle', 'pkde_kit/shop_filters_title', 'pkde_kit/shop_before_results', 'pkde_kit/shop_after_results', 'pkde_kit/shop_page_header', '--pkde-shop-line', '--pkde-shop-accent', '--pkde-shop-muted', '--pkde-shop-ink', '--pkde-shop-surface', '--pkde-shop-radius', '--pkde-shop-panel-radius', '--pkde-shop-gap', '--pkde-shop-filters-width', '--pkde-shop-sticky-top', '--pkde-shop-heading-weight', '--pkde-shop-columns', '--pkde-shop-columns-tablet', '--pkde-shop-columns-phone', '--pkde-shop-pad-top'] as $name) {
        expect($doc)->toContain($name);
    }
});

it('gives the product grid a fixed column count from variables, immune to a site\'s columns-N rules and the results width', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($css)->toContain('.shop-archive .shop-archive__results ul.products.products[class]', 'repeat(var(--pkde-shop-cols), minmax(0, 1fr))', 'var(--pkde-shop-columns, 4)', 'var(--pkde-shop-columns-tablet, 3)', 'var(--pkde-shop-columns-phone, 2)')
        ->and($css)->not->toContain('repeat(auto-fill, minmax(min(100%, 13.5rem)');
});

it('takes the page header\'s space from variables and keeps a site\'s paragraph style out of the breadcrumb trail', function () use ($root) {
    $css = file_get_contents("$root/resources/css/page-header.css");
    $view = file_get_contents("$root/resources/views/partials/page-header.blade.php");

    expect($css)->toContain('--pkde-page-header-padding:', '--pkde-page-header-padding-lg:', '.page-header__inner', '.breadcrumbs p', 'font: inherit')
        ->and($view)->toContain('page-header__inner')->not->toContain('py-14');
});

it('keeps a Section that holds only the Shop products block from stacking its padding and block gap above the filters (tcs Shop page)', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($css)->toContain('.wp-block-sage-section:has(> .wp-block-sage-shop-products):not(:has(> :not(.wp-block-sage-shop-products, .wp-block-hidden-desktop.wp-block-hidden-tablet.wp-block-hidden-mobile)))', 'padding-top: 0;', 'margin-block-start: 0;')
        ->and($css)->toContain('ul.products.products[class]');
});

it('keeps the filter lists from showing a scrollbar they do not need, and keeps the scroll for long lists (owner, bb)', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $rule = substr($css, strpos($css, '#shop-filters ul {'), 760);

    expect($rule)->toContain('max-height: 14rem;', 'overflow-y: auto;', 'overflow-x: hidden;', 'padding-block: 2px;', 'margin-top: calc(0.75rem - 2px);')
        ->and($rule)->not->toContain('overflow: hidden')
        ->and(file_get_contents("$root/resources/views/partials/sidebar-shop.blade.php"))->not->toContain('[&_ul]:space-y-2')
        ->and(file_get_contents("$root/resources/views/partials/shop-term-filter.blade.php"))->toContain('<ul class="grid gap-2 text-sm">');
});

it('draws the grid / list toggle in the site\'s brand through variables, with the icons in currentColor and no hard-coded neutral for the pressed state', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");
    $block = substr($css, strpos($css, '.pkde-view-toggle__button {'), 1500);

    expect($block)->toContain('--pkde-view-toggle-border', '--pkde-view-toggle-color', '--pkde-view-toggle-hover', '--pkde-view-toggle-active-bg', '--pkde-view-toggle-active-fg', 'var(--color-brand-600)', 'var(--color-brand-900)', 'var(--color-brand-900-fg, var(--color-white))')
        ->and($block)->not->toContain('--color-neutral-950')->not->toContain('--color-neutral-500')
        ->and(file_get_contents("$root/app/woocommerce/view-toggle.php"))->toContain('currentColor');
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

it('lets a site colour the stars with one variable and honours the Product Loop block\'s column count (MGR-78)', function () {
    $root = dirname(__DIR__, 2);
    $shop = file_get_contents("$root/resources/css/shop-pages.css");
    $kit = file_get_contents("$root/resources/css/kit.css");

    expect(substr_count($shop, 'var(--pkde-star-fill, var(--color-amber-700))'))->toBe(2)
        ->and($shop)->not->toContain('background: var(--color-amber-700)')
        ->and($kit)->toContain('.woocommerce.columns-4 ul.products { grid-template-columns: repeat(4, minmax(0, 1fr)); }', '.woocommerce.columns-2 ul.products');
});
