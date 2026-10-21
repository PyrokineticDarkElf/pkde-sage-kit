<?php

/**
 * Product Loop block output: the shop's own [products] loop, so cards look and behave exactly as everywhere else. Does nothing
 * when WooCommerce is not active.
 *
 * @var array $attributes
 */

if (! function_exists('wc_get_products')) {
    return;
}

// In the editor preview (a REST request) WooCommerce has not loaded its cart/session; the product cards need them.
if (function_exists('wc_load_cart') && (defined('REST_REQUEST') && REST_REQUEST) && ! WC()->cart) {
    wc_load_cart();
}

$limit = max(1, min(48, (int) ($attributes['limit'] ?? 8)));
$columns = max(1, min(6, (int) ($attributes['columns'] ?? 4)));
$orderby = in_array($attributes['orderby'] ?? '', ['menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc', 'rand', 'title'], true) ? $attributes['orderby'] : 'menu_order';
$order = ($attributes['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$include = array_values(array_filter(array_map('sanitize_title', (array) ($attributes['categories'] ?? []))));
$exclude = array_values(array_filter(array_map('sanitize_title', (array) ($attributes['excludeCategories'] ?? []))));

$shortcode = sprintf('[products limit="%d" columns="%d" orderby="%s" order="%s"', $limit, $columns, $orderby, $order);

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

echo '<div ' . get_block_wrapper_attributes() . '>' . do_shortcode($shortcode . ']') . '</div>';

if (isset($filter)) {
    remove_filter('woocommerce_shortcode_products_query', $filter, 10);
}
