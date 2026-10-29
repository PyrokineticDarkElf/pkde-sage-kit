<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Featured Products block frontend output. No-ops entirely when
 * WooCommerce isn't active.
 *
 * @var array $attributes
 */

if (! class_exists('WooCommerce')) {
    return;
}

$source = $attributes['source'] ?? 'featured';
$count = max(1, (int) ($attributes['count'] ?? 4));
$skus_raw = $attributes['skus'] ?? '';

$query_args = [
    'post_type' => 'product',
    'post_status' => 'publish',
];

if ($source === 'manual') {
    $skus = array_filter(array_map('trim', explode(',', $skus_raw)));
    $product_ids = array_filter(array_map('wc_get_product_id_by_sku', $skus));

    if (empty($product_ids)) {
        $query_args['post__in'] = [0];
    } else {
        $query_args['post__in'] = $product_ids;
        $query_args['orderby'] = 'post__in';
        $query_args['posts_per_page'] = count($product_ids);
    }
} else {
    $featured_ids = wc_get_featured_product_ids();

    if (empty($featured_ids)) {
        $query_args['post__in'] = [0];
    } else {
        $query_args['post__in'] = $featured_ids;
        $query_args['posts_per_page'] = $count;
    }
}

$query = new WP_Query($query_args);

// ?featured=1 on the Shop page — WooCommerce's classic template has no
// built-in URL filter for this, so app/woocommerce/filter-query.php adds
// one, reusing the exact same wc_get_featured_product_ids() this block
// queries from. Only offered for the "featured" source: a manually-curated
// SKU list has no equivalent "view all" — there's no larger set behind it.
$view_all_url = ($source !== 'manual' && class_exists('WooCommerce'))
    ? add_query_arg('featured', '1', wc_get_page_permalink('shop'))
    : '';

$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Featured Products', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => __('View all featured', 'sage'),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view('pkde-kit::partials.blocks.product-grid', [
        'query' => $query,
        'cardStyle' => $card_style,
        'emptyMessage' => $source === 'manual'
            ? __('No products selected yet — check the SKUs in the block settings.', 'sage')
            : __('No products are marked as featured yet — check "This is a featured product" on a product to feature it here.', 'sage'),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
