<?php

/**
 * WooCommerce CSV importer enhancements.
 *
 * This file is loaded from app/woocommerce.php only after WooCommerce has
 * confirmed it is active. Keep importer-specific mappings here rather than
 * mixing them into storefront hooks and theme-support configuration.
 */

namespace App;

/**
 * Make product slugs a first-class WooCommerce CSV import field. The explicit
 * mapping option supports manual importer mapping, while the default mapping
 * recognises a CSV column headed "Slug" without further admin intervention.
 */
// WP-105 phase 1: pkde/platform's `woocommerce-import-slug` module does this when it is on (it defines PKDE_PLATFORM_WOOCOMMERCE_IMPORT_SLUG); PKDE_PLATFORM_DISABLE brings this copy back.
if (! defined('PKDE_PLATFORM_WOOCOMMERCE_IMPORT_SLUG')) {
    add_filter('woocommerce_csv_product_import_mapping_options', function (array $options): array {
        $options['slug'] = __('Slug', 'sage');

        return $options;
    });

    add_filter('woocommerce_csv_product_import_mapping_default_columns', function (array $columns): array {
        $columns['Slug'] = 'slug';

        return $columns;
    });
}
