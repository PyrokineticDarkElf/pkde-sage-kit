<?php

/**
 * An Image block directly in a Grid fills its cell, and the earlier filter_product_* migration is gone.
 */
$root = dirname(__DIR__, 2);

it('fills the grid cell with an image block, in unlayered CSS so core\'s img rule does not win', function () use ($root) {
    $css = file_get_contents("$root/resources/css/kit.css");
    // the accordion item rule is the first unlayered rule of the file; the grid rule must come after it
    $after = substr($css, (int) strpos($css, '.pkde-section .wp-block-sage-accordion-item {'));

    expect($after)->toContain('.is-layout-grid > .wp-block-image {')
        ->and($after)->toContain('.is-layout-grid > .wp-block-image img {')
        ->and($after)->toContain('height: 100%');
});

it('no longer migrates the array-valued filter_product_cat / filter_product_tag URLs', function () use ($root) {
    expect(file_get_contents("$root/app/woocommerce/filter-query.php"))->not->toContain('migrateLegacyFilterParameters');
});
