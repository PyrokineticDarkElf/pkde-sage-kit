<?php

/**
 * The product gallery reserves its final height before WooCommerce's script runs (WP-91, TPL-181). The measurements are in the changelog
 * (real Chrome, throttled: bb layout shift 0.30 to 0 on a phone, 0.09 to 0 on a desktop); this keeps the four rules from being edited away.
 */
it('shows the gallery at once, one picture before the script, a 4:3 viewport and room for the thumbnails', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');
    $gallery = '.woocommerce div.product .woocommerce-product-gallery';

    expect($css)->toContain("$gallery {\n    opacity: 1 !important;")
        ->and($css)->toContain("$gallery:not(:has(.flex-viewport)) .woocommerce-product-gallery__image:nth-child(n + 2) {\n    display: none;")
        ->and($css)->toContain("$gallery .flex-viewport {\n    height: auto !important;\n    aspect-ratio: 4 / 3;")
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
