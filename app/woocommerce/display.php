<?php

/**
 * WooCommerce markup/layout integration: dequeues WC's bundled styles and
 * classic wrappers in favour of the theme's own Blade markup and Tailwind
 * pass, and adjusts loop/product-page display details to match the rest of
 * the theme.
 *
 * This file is loaded from app/woocommerce.php only after WooCommerce has
 * confirmed it is active.
 */

namespace App;

// Our own Tailwind pass (resources/css/app.css) styles WooCommerce's markup —
// its bundled stylesheets would otherwise fight it on specificity.
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/*
 * Blade owns the outer page layout and renders the site breadcrumb itself.
 * Removing WooCommerce's classic wrappers prevents nested .woocommerce
 * containers and duplicate breadcrumbs, while leaving its product-loop and
 * purchase hooks intact.
 */
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

// Notices are rendered once, directly below the page context in each Blade
// template. This prevents the archive/single defaults from outputting them a
// second time inside the product loop.
remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);

// Products per row and rows per page are WooCommerce's own settings (Customize > WooCommerce > Product Catalog; defaults in
// app/woocommerce.php), not theme overrides. Product pages show four gallery thumbnails per row.
add_filter('woocommerce_product_thumbnails_columns', fn() => 4);

// The theme's page header prints the page title (an H1) once; WooCommerce's own title inside the product list would repeat it.
add_filter('woocommerce_show_page_title', '__return_false');

// The shop's own content (the Shop page in the editor) is printed by App\View\Composers\ProductArchive, so WooCommerce's copy of it
// above the product list is removed. A category's description stays WooCommerce's own.
remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

// The product card (resources/views/woocommerce/content-product.blade.php, loaded through the theme's woocommerce/content-product.php)
// draws these itself and fires the five card hooks in their usual places so plugins can still add to it. WooCommerce's own output
// on those hooks is removed here, apart from the add-to-cart button, which the last hook prints.
remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);

/**
 * Category/tag pills directly under the product title, replacing WC's plain
 * "Category: Tags:" text meta line — same pill styling already used for the
 * blog (partials/term-pills.blade.php). Priority 6 puts it right after the
 * title (5) and before rating/price (10), so it reads as part of the header
 * rather than buried at the bottom with the default meta (40).
 */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
add_action('woocommerce_single_product_summary', function () {
    global $product;

    if (! $product instanceof \WC_Product) {
        return;
    }

    $categories = get_the_terms($product->get_id(), 'product_cat');
    $tags = get_the_terms($product->get_id(), 'product_tag');
    $terms = array_merge(
        is_wp_error($categories) ? [] : ($categories ?: []),
        is_wp_error($tags) ? [] : ($tags ?: []),
    );

    echo view('pkde-kit::partials.term-pills', ['terms' => $terms])->render();
}, 6);

// partials/page-header.blade.php uses get_the_archive_title(), which prefixes
// post-type/taxonomy archives with a generic "Archives: "/"Product category: "
// label — drop it here so the shop/category pages just read "Shop"/"Category One".
add_filter('get_the_archive_title', function ($title) {
    if (is_shop() || is_product_taxonomy()) {
        $title = preg_replace('/^\S+:\s*/', '', $title);
    }

    return $title;
});
