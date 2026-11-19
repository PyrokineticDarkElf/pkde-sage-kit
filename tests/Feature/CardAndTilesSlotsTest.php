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
