<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Product Loop block output: the shop's own [products] loop, so cards look and behave exactly as everywhere else. Does nothing
 * when WooCommerce is not active.
 *
 * @var array $attributes
 */

if (! function_exists('wc_get_products')) {
    return;
}

// In the editor preview (a REST request) WooCommerce has not loaded its cart/session; the product cards need them: app/woocommerce.php loads them for the block-renderer route.

$limit = max(1, min(48, (int) ($attributes['limit'] ?? 8)));
$columns = max(1, min(6, (int) ($attributes['columns'] ?? 4)));
$orderby = in_array($attributes['orderby'] ?? '', ['menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc', 'rand', 'title'], true) ? $attributes['orderby'] : 'menu_order';
$order = ($attributes['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$include = array_values(array_filter(array_map('sanitize_title', (array) ($attributes['categories'] ?? []))));
$exclude = array_values(array_filter(array_map('sanitize_title', (array) ($attributes['excludeCategories'] ?? []))));

$featured = ($attributes['source'] ?? 'all') === 'featured';
$on_sale = ($attributes['source'] ?? 'all') === 'onsale';
$shortcode = sprintf('[products limit="%d" columns="%d" orderby="%s" order="%s"', $limit, $columns, $orderby, $order);

// the products marked "This is a featured product" (WooCommerce's own visibility setting of [products])
if ($featured) {
    $shortcode .= ' visibility="featured"';
}

// the products with a sale price (WooCommerce's own on_sale setting of [products])
if ($on_sale) {
    $shortcode .= ' on_sale="true"';
}

if ($include) {
    $shortcode .= ' category="' . implode(',', $include) . '"';
}

// [products] has no "leave out" setting for a category list that is also being included, so the exclusion rides on the
// shortcode's own class attribute and is applied to this one query.
if ($exclude) {
    $marker = 'product-loop-' . substr(md5(implode(',', $exclude)), 0, 8);
    $shortcode .= ' class="' . $marker . '"';
    $filter = function ($query, $shortcodeAttributes) use ($exclude, $marker) {
        if (($shortcodeAttributes['class'] ?? '') === $marker) {
            $query['tax_query'][] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $exclude, 'operator' => 'NOT IN'];
        }

        return $query;
    };
    add_filter('woocommerce_shortcode_products_query', $filter, 10, 2);
}

// Optional section header, theme and card style, the same three the Recent, Best Selling and Featured blocks have. With none set (the defaults)
// the output is what it always was: the wrapper and the shortcode's grid.
$heading = trim((string) ($attributes['heading'] ?? ''));
$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$view_all_url = '';

if (! empty($attributes['viewAll']) && function_exists('wc_get_page_permalink')) {
    // WooCommerce's own catalogue ordering understands these, so the link shows the shop in the order the block uses
    $view_all_url = in_array($orderby, ['popularity', 'rating', 'date', 'price', 'price-desc'], true) ? add_query_arg('orderby', $orderby, wc_get_page_permalink('shop')) : wc_get_page_permalink('shop');

    // app/woocommerce/filter-query.php lets the shop page list only the featured products (?featured=1)
    if ($featured) {
        $view_all_url = add_query_arg('featured', '1', $view_all_url);
    }
}

$loop_layout = in_array($attributes['layout'] ?? 'grid', ['grid', 'rail', 'feature', 'intro'], true) ? $attributes['layout'] : 'grid';
$look = in_array($attributes['cardLook'] ?? '', ['tiles', 'centred'], true) ? $attributes['cardLook'] : ''; // Tiles: a white card with the picture inset, a SKU pill, a bold price, a full-width button. Centred: the picture in a white tile, the name, price and a rounded button centred under it
$tiles = $look === 'tiles';
// "View all" as a tab under the products (a pill, or pinned to the Section's bottom edge) instead of the link beside the heading
$tab_mode = in_array($attributes['viewAllTab'] ?? '', ['tab', 'pinned', 'link'], true) && $view_all_url !== '';
$loop_classes = trim(($loop_layout === 'grid' && $look === '' ? '' : 'pkde-products') . ($loop_layout === 'grid' ? '' : " pkde-products--{$loop_layout}") . ($look !== '' ? " pkde-products--{$look}" : '') . (! empty($attributes['headingTab']) ? ' has-heading-tab' : '') . ($tab_mode && $attributes['viewAllTab'] === 'pinned' ? ' has-pinned-tab' : ''));
echo '<div ' . get_block_wrapper_attributes(\App\card_wrapper_args($attributes, trim(\App\pkde_loop_theme_classes($theme) . ($loop_classes !== '' ? " {$loop_classes}" : '')))) . '>';
echo \App\pkde_loop_theme_content_open($theme);
echo view('pkde-kit::partials.blocks.section-header', [
    'heading' => $heading,
    'description' => (string) ($attributes['description'] ?? ''),
    'viewAllUrl' => $tab_mode ? null : $view_all_url,
    'tab' => ! empty($attributes['headingTab']),
    'viewAllLabel' => __('View all products', 'sage'),
    'themed' => (bool) $theme,
])->render();
// the card reads its style from this bridge (partials/blocks/product-grid.blade.php explains why); reset after, so it cannot leak into a later render
$GLOBALS['pkde_product_card_style'] = $card_style;
$GLOBALS['pkde_product_card_look'] = $tiles ? 'tiles' : '';
$products_html = do_shortcode($shortcode . ']');
$GLOBALS['pkde_product_card_style'] = null;
$GLOBALS['pkde_product_card_look'] = null;

if ($loop_layout === 'rail' && str_contains($products_html, '<ul class="products')) {
    // The row that scrolls is the same carousel as the People row (resources/js/carousel.js): the shop's wrapper is its viewport, the list its track, and the dots are made by the script
    $products_html = preg_replace('/<div class="woocommerce /', '<div data-carousel-viewport class="woocommerce ', $products_html, 1);
    $products_html = preg_replace('/<ul class="products/', '<ul data-carousel-track class="products', $products_html, 1);
    $products_html = '<div class="pkde-products__carousel" data-carousel data-carousel-loop="false">' . $products_html
        . '<div class="pkde-features__controls"><div class="pkde-features__dots" data-carousel-dots data-label="' . esc_attr__('Go to position', 'sage') . '"></div></div></div>';
}

echo $products_html;

if ($tab_mode) {
    echo view('pkde-kit::partials.blocks.view-all-tab', ['url' => $view_all_url, 'label' => $attributes['viewAllTab'] === 'link' ? __('View more', 'sage') : __('Shop all', 'sage'), 'link' => $attributes['viewAllTab'] === 'link'])->render();
}
echo \App\pkde_loop_theme_content_close($theme);
echo '</div>';

if (isset($filter)) {
    remove_filter('woocommerce_shortcode_products_query', $filter, 10);
}
