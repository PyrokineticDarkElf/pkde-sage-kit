<?php

/**
 * Brands block frontend output. No-ops entirely when WooCommerce isn't
 * active — the taxonomy simply doesn't exist.
 *
 * @var array $attributes
 */

if (! taxonomy_exists('product_brand')) {
    return;
}

$count = max(0, (int) ($attributes['count'] ?? 0));

$terms = get_terms([
    'taxonomy' => 'product_brand',
    'hide_empty' => true,
    'number' => $count,
]);

// bin/setup always seeds this page (post_name=brands) when WooCommerce is
// set up — get_page_by_path() rather than a hardcoded home_url('/brands/')
// so this still resolves correctly if a client ever renames the page.
$brands_page = get_page_by_path('brands');
$view_all_url = $brands_page ? get_permalink($brands_page) : '';

$theme = sanitize_html_class($attributes['theme'] ?? '');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Brands', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => __('View all brands', 'sage'),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view('pkde-kit::partials.blocks.brand-cards', [
        'terms' => is_wp_error($terms) ? [] : $terms,
        'emptyMessage' => __('No brands yet.', 'sage'),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
