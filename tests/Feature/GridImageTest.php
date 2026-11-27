<?php

/**
 * An Image block directly in a Grid fills its cell, and the earlier filter_product_* migration is gone.
 */
$root = dirname(__DIR__, 2);

it('no longer migrates the array-valued filter_product_cat / filter_product_tag URLs', function () use ($root) {
    expect(file_get_contents("$root/app/woocommerce/filter-query.php"))->not->toContain('migrateLegacyFilterParameters');
});
