<?php

/**
 * The product gallery reserves its final height before WooCommerce's script runs (WP-91, TPL-181). The measurements are in the changelog
 * (real Chrome, throttled: bb layout shift 0.30 to 0 on a phone, 0.09 to 0 on a desktop); this keeps the four rules from being edited away.
 */
it('shows the gallery at once, one picture before the script, a viewport as high as the picture and room for the thumbnails', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');
    $gallery = '.woocommerce div.product .woocommerce-product-gallery';

    expect($css)->toContain("$gallery {\n    opacity: 1 !important;")
        ->and($css)->toContain("$gallery:not(:has(.flex-viewport)) .woocommerce-product-gallery__image:nth-child(n + 2) {\n    display: none;")
        ->and($css)->toContain("$gallery .flex-viewport {\n    height: auto !important;\n    aspect-ratio: var(--pkde-gallery-ratio, auto);")
        ->and($css)->not->toContain("height: auto !important;\n    aspect-ratio: 4 / 3;") // a fixed 4:3 clipped tcs's square pictures (TCS-131)
        ->and($css)->toContain(':not(:has(.flex-control-thumbs))::after')
        ->and($css)->toContain('padding-top: calc(25% - 0.5625rem)');
});

it('gives the first hero the LCP attributes, and the category and brand cards a sized, lazy attachment image', function () {
    $root = dirname(__DIR__, 2);
    $hero = file_get_contents("$root/resources/blocks/hero/render.php");

    expect($hero)->toContain("\\App\\next_hero_slide_number() === 1 ? ['loading' => 'eager', 'fetchpriority' => 'high'] : ['loading' => 'lazy']");

    foreach (['category-cards', 'brand-cards'] as $view) {
        $source = file_get_contents("$root/resources/views/partials/blocks/$view.blade.php");

        expect($source)->toContain("wp_get_attachment_image(\$imageId, 'medium_large', false, [")->toContain("'loading' => 'lazy'")->not->toContain('<img');
    }
});

it('gives the viewport a box for the instant before the script sets its height, from the picture ratio the kit shows (TPL-244)', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');
    $gallery = '.woocommerce div.product .woocommerce-product-gallery';

    // until FlexSlider's inline height is there the viewport is 4:3 (or the site's ratio): without it the stacked pictures made it 14,000 px tall for a moment
    expect($css)->toContain("$gallery .flex-viewport:not([style*=\"height\"]) {\n    aspect-ratio: var(--pkde-gallery-ratio, 4 / 3);")
        // the pictures follow the same variable, so a site that sets it gets viewport and pictures at one ratio
        ->and($css)->toContain("$gallery img {\n    width: 100%;\n    aspect-ratio: var(--pkde-gallery-ratio, 4 / 3);")
        ->and($css)->not->toContain('--pkde-gallery-first-ratio'); // a picture's own ratio is not the one the kit shows it at (it crops to 4:3): bb would be clipped
});
