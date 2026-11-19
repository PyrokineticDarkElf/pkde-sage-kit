<?php

/**
 * TPL-193 (from the tcs and bb copies of two kit views): category tiles render URL images through the media library and never list "Uncategorized"; the product
 * card's picture takes its fit and its `sizes` from a variable and a filter instead of a copy of the view.
 */
$root = dirname(__DIR__, 2);

it('finds the original of a resized upload URL', function () {
    expect(\App\strip_image_size('https://example.test/app/uploads/2026/11/clock-300x200.jpg'))->toBe('https://example.test/app/uploads/2026/11/clock.jpg')
        ->and(\App\strip_image_size('/app/uploads/clock-1024x768.webp'))->toBe('/app/uploads/clock.webp')
        ->and(\App\strip_image_size('https://example.test/app/uploads/clock.jpg'))->toBe('https://example.test/app/uploads/clock.jpg')
        ->and(\App\strip_image_size('https://example.test/img-2x3-final.jpg'))->toBe('https://example.test/img-2x3-final.jpg');
});

it('prints the category tile images through the media library and leaves out Uncategorized', function () use ($root) {
    $view = file_get_contents("$root/resources/views/partials/blocks/category-tiles.blade.php");

    expect($view)->toContain("fn (\$term) => \$term->slug !== 'uncategorized'")
        ->and($view)->toContain('\\App\\media_library_image($fallback')
        ->and($view)->toContain("\\App\\media_library_image(\$tile['image']")
        ->and($view)->not->toContain('<img');
});

it('gives the product card picture a fit variable and a sizes filter, not a fixed object-cover', function () use ($root) {
    $card = file_get_contents("$root/resources/views/woocommerce/content-product.blade.php");
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($card)->toContain("apply_filters('pkde_kit/card_image_sizes', '')")
        ->and($card)->not->toContain('object-cover')
        ->and($css)->toContain('.pkde-product-card__media img {')
        ->and($css)->toContain('object-fit: var(--pkde-card-image-fit, cover);');
});

it('styles the category tiles from variables with stable class hooks, and imports the file', function () use ($root) {
    $css = file_get_contents("$root/resources/css/category-tiles.css");
    $view = file_get_contents("$root/resources/views/partials/blocks/category-tiles.blade.php");

    foreach (['--pkde-tile-cols-sm', '--pkde-tile-cols-lg', '--pkde-tile-gap-x', '--pkde-tile-gap-y', '--pkde-tile-aspect', '--pkde-tile-radius', '--pkde-tile-bg', '--pkde-tile-hover-scale', '--pkde-tile-hover-duration', '--pkde-tile-title-size', '--pkde-tile-title-weight', '--pkde-tile-image-fit', '--pkde-tile-image-padding', '--pkde-tile-title-transform', '--pkde-tile-title-leading', '--pkde-tile-title-spacing', '--pkde-tile-title-align', '--pkde-tile-title-gap', '--pkde-tile-title-color', '--pkde-tile-title-hover'] as $variable) {
        expect($css)->toContain("var($variable");
    }

    // the defaults are the kit's earlier look: 2 and 4 columns, gaps 1.5rem and 2.5rem, 4 / 3, rounded 1rem, zoom 1.03, a 1.125rem semibold title
    expect($css)->toContain('var(--pkde-tile-cols-lg, 4)')->toContain('var(--pkde-tile-aspect, 4 / 3)')->toContain('var(--pkde-tile-radius, 1rem)')->toContain('var(--pkde-tile-hover-scale, 1.03)')
        ->and($view)->toContain('class="pkde-tiles"')->toContain('pkde-tile__media')->toContain('pkde-tile__title')->toContain("apply_filters('pkde_kit/tile_image_size', 'medium_large')")
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./category-tiles.css";');
});

it('has slots on the post card and the page header, called with their defaults changing nothing', function () use ($root) {
    $card = file_get_contents("$root/resources/views/partials/content.blade.php");
    $header = file_get_contents("$root/resources/views/partials/page-header.blade.php");

    expect($card)->toContain("apply_filters('pkde_kit/card_media', null, get_post())")
        ->and($card)->toContain("apply_filters('pkde_kit/card_show_meta', true, get_post())")
        ->and($card)->toContain('has_post_thumbnail()') // the default picture is still there when the filter returns nothing
        ->and($header)->toContain("do_action('pkde_kit/page_header_start', \$title)")
        ->and($header)->toContain("do_action('pkde_kit/page_header_after_title', \$title)")
        ->and(strpos($header, "do_action('pkde_kit/page_header_start'"))->toBeLessThan(strpos($header, '<h1'))
        ->and(strpos($header, '<h1'))->toBeLessThan(strpos($header, "do_action('pkde_kit/page_header_after_title'"));
});

it('styles the Contact Form 7 form from variables whose defaults are the earlier look', function () use ($root) {
    $css = file_get_contents("$root/resources/css/kit.css");

    foreach (['--pkde-form-columns, 2', '--pkde-form-gap, 1.5rem', '--pkde-form-label-color, var(--color-neutral-900)', '--pkde-form-label-size, 0.875rem', '--pkde-form-label-weight, 500', '--pkde-form-label-gap, 0.5rem', '--pkde-form-field-border, var(--color-neutral-300)', '--pkde-form-field-radius, var(--radius-xl)', '--pkde-form-field-bg, var(--color-white)', '--pkde-form-focus, var(--color-brand-600)', '--pkde-form-focus-ring, var(--color-brand-100)', '--pkde-form-submit-bg, var(--color-neutral-950)', '--pkde-form-submit-color, var(--color-white)', '--pkde-form-submit-radius, 9999px', '--pkde-form-submit-hover-bg, var(--color-brand-700)', '--pkde-form-submit-hover-color, var(--color-brand-700-fg)'] as $variable) {
        expect($css)->toContain("var($variable)");
    }
});
