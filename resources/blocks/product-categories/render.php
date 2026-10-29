<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Product Categories block frontend output. No-ops entirely when
 * WooCommerce isn't active — the taxonomy simply doesn't exist.
 *
 * @var array $attributes
 */

if (! taxonomy_exists('product_cat')) {
    return;
}

$count = max(0, (int) ($attributes['count'] ?? 0));
$layout = ($attributes['layout'] ?? 'cards') === 'tiles' ? 'tiles' : 'cards';

// Optional selection. With none of these set the block behaves exactly as it always did (every non-empty category, optionally
// limited by "Number of categories"): existing pages are unchanged.
$slugs = fn($list) => array_values(array_filter(array_map('sanitize_title', (array) $list)));
$include = $slugs($attributes['include'] ?? []);
$exclude = $slugs($attributes['exclude'] ?? []);
$labels = is_array($attributes['labels'] ?? null) ? $attributes['labels'] : [];
$extra = array_values(array_filter((array) ($attributes['extra'] ?? []), fn($tile) => is_array($tile) && ! empty($tile['label']) && ! empty($tile['url'])));

if ($include) {
    // The chosen categories, in the chosen order.
    $terms = array_values(array_filter(array_map(fn($slug) => get_term_by('slug', $slug, 'product_cat'), $include)));
} else {
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'number' => $count,
    ]);
    $terms = is_wp_error($terms) ? [] : $terms;
}

if ($exclude) {
    $terms = array_values(array_filter($terms, fn($term) => ! in_array($term->slug, $exclude, true)));
}

if ($include && $count > 0) {
    $terms = array_slice($terms, 0, $count);
}

// bin/setup always seeds this page (post_name=categories) alongside Brands
// when WooCommerce is set up — get_page_by_path() rather than a hardcoded
// URL, so this still resolves correctly if a client renames the page.
$categories_page = get_page_by_path('categories');
$view_all_url = $categories_page ? get_permalink($categories_page) : '';

$theme = sanitize_html_class($attributes['theme'] ?? '');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Product Categories', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => __('View all categories', 'sage'),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view($layout === 'tiles' ? 'pkde-kit::partials.blocks.category-tiles' : 'pkde-kit::partials.blocks.category-cards', [
        'terms' => $terms,
        'labels' => $labels,
        'extra' => $extra,
        'emptyMessage' => __('No product categories yet.', 'sage'),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
