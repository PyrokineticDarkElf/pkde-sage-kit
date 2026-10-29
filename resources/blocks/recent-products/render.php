<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Recent Products block frontend output. No-ops entirely when WooCommerce
 * isn't active.
 *
 * @var array $attributes
 */

if (! class_exists('WooCommerce')) {
    return;
}

$count = max(1, (int) ($attributes['count'] ?? 4));

$query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => $count,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// WooCommerce's own catalogue-ordering dropdown already supports
// ?orderby=date on the Shop page natively — no custom query handling needed
// to make this link show the same "newest first" order as the block.
$view_all_url = class_exists('WooCommerce') ? add_query_arg('orderby', 'date', wc_get_page_permalink('shop')) : '';

$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Recent Products', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => __('View all products', 'sage'),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view('pkde-kit::partials.blocks.product-grid', [
        'query' => $query,
        'cardStyle' => $card_style,
        'emptyMessage' => __('No products yet.', 'sage'),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
