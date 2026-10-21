<?php

/**
 * Stop WooCommerce's own template_include filters from overriding Acorn's
 * Blade template resolution.
 *
 * This file is loaded from app/woocommerce.php only after WooCommerce has
 * confirmed it is active.
 */

namespace App;

/**
 * WC_Template_Loader::template_loader() hooks template_include and resolves
 * archive-product/single-product by calling WordPress core's locate_template()
 * directly — a raw filesystem scan for a literal .php file — which never
 * finds Acorn's compiled Blade views, so it always falls back to WooCommerce's
 * own bundled default templates. Acorn already resolves archive-product.blade.php/
 * single-product.blade.php correctly via the earlier archive_template_hierarchy/
 * single_template_hierarchy filters (see FiltersTemplates::filterTemplateHierarchy
 * in vendor/roots/acorn), so we just need WooCommerce to stop overriding that
 * result after the fact.
 *
 * WC only registers that filter once its own `init` callback runs (and only
 * once add_theme_support('woocommerce') has already fired on
 * after_setup_theme, which it has by the time `init` runs) — both WC's
 * registration and this removal are on `init`, so this has to run at a
 * later priority or there's nothing registered yet to remove.
 *
 * WC_Brands (Woo's own product_brand taxonomy) registers a SECOND, separate
 * `template_include` filter of its own — an instance method, not the static
 * WC_Template_Loader one above, so removing that one doesn't touch this one.
 * It does the exact same raw-locate_template()-then-plugin-bundled-fallback
 * thing, just for brand archive/single pages, which is what was silently
 * serving WooCommerce's unstyled bundled archive-product.php on brand pages
 * instead of our taxonomy-product_brand.blade.php. It's registered on
 * `plugins_loaded` priority 11 (before `init` even fires), so it's already
 * there by the time our `init` callback runs.
 */
add_action('init', function () {
    remove_filter('template_include', ['WC_Template_Loader', 'template_loader']);

    if (! empty($GLOBALS['WC_Brands'])) {
        remove_filter('template_include', [$GLOBALS['WC_Brands'], 'template_loader']);
    }
}, 20);

/*
 * Product categories, tags and brands use the shop's template (archive-product), as they do in WooCommerce itself, so there are
 * no separate taxonomy templates to keep in sync. A more specific taxonomy-product_cat(-slug) view still wins. Runs before Acorn
 * turns the list into view names (its filter is at priority 10).
 */
add_filter('taxonomy_template_hierarchy', function (array $templates): array {
    if (is_product_taxonomy()) {
        $at = array_search('taxonomy.php', $templates, true);
        array_splice($templates, $at === false ? count($templates) : $at, 0, ['archive-product.php']);
    }

    return $templates;
}, 9);
