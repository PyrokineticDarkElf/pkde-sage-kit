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
$shortcode = sprintf('[products limit="%d" columns="%d" orderby="%s" order="%s"', $limit, $columns, $orderby, $order);

// the products marked "This is a featured product" (WooCommerce's own visibility setting of [products])
if ($featured) {
    $shortcode .= ' visibility="featured"';
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
echo '<div ' . get_block_wrapper_attributes(\App\card_wrapper_args($attributes, trim(\App\pkde_loop_theme_classes($theme) . ($loop_layout === 'grid' ? '' : " pkde-products pkde-products--{$loop_layout}")))) . '>';
echo \App\pkde_loop_theme_content_open($theme);
echo view('pkde-kit::partials.blocks.section-header', [
    'heading' => $heading,
    'description' => (string) ($attributes['description'] ?? ''),
    'viewAllUrl' => $view_all_url,
    'viewAllLabel' => __('View all products', 'sage'),
    'themed' => (bool) $theme,
])->render();
// the card reads its style from this bridge (partials/blocks/product-grid.blade.php explains why); reset after, so it cannot leak into a later render
$GLOBALS['pkde_product_card_style'] = $card_style;
echo do_shortcode($shortcode . ']');
$GLOBALS['pkde_product_card_style'] = null;
echo \App\pkde_loop_theme_content_close($theme);
echo '</div>';

if (isset($filter)) {
    remove_filter('woocommerce_shortcode_products_query', $filter, 10);
}
