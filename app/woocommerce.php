<?php

/**
 * WooCommerce theme integration. No-ops entirely when the plugin isn't
 * installed/active — the non-ecommerce path is completely unaffected.
 *
 * Split by concern across app/woocommerce/: CSV importer mapping
 * (importer.php), Blade template-resolution fixes (template-resolution.php),
 * shop-filter query wiring (filter-query.php, alongside ShopFilters.php), and
 * markup/layout integration (display.php). This file only registers theme
 * support and loads those pieces.
 */

namespace App;

// Loaded by Composer's autoloader (autoload.files), also outside WordPress (the kit's own tests, Composer scripts): only the pure
// functions of helpers.php are wanted there.
if (! function_exists('add_action')) {
    return;
}

if (! class_exists('WooCommerce')) {
    return;
}

require_once __DIR__ . '/woocommerce/importer.php';
require_once __DIR__ . '/woocommerce/template-resolution.php';
require_once __DIR__ . '/woocommerce/filter-query.php';
require_once __DIR__ . '/woocommerce/display.php';

add_action('after_setup_theme', function () {
    // Product Catalog defaults (Appearance > Customize > WooCommerce > Product Catalog): three per row, four rows per page.
    // The shop's own settings decide from there; the theme does not override the grid, the page size, related products or labels.
    add_theme_support('woocommerce', ['product_grid' => ['default_columns' => 3, 'default_rows' => 4]]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
});
