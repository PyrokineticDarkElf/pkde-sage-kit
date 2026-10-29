<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Latest Posts block frontend output.
 *
 * @var array $attributes
 */

$count = max(1, (int) ($attributes['count'] ?? 3));
$post_type = sanitize_key($attributes['postType'] ?? 'post');
if (! post_type_exists($post_type)) {
    $post_type = 'post';
}
// The 'category' taxonomy is only registered against 'post' by default —
// a categoryId set from an earlier postType='post' use of this same block
// is ignored once it's pointed at a different post type, rather than
// silently filtering by a taxonomy that post type doesn't have.
$category_id = $post_type === 'post' ? (int) ($attributes['categoryId'] ?? 0) : 0;

$query_args = [
    'post_type' => $post_type,
    'posts_per_page' => $count,
    'post_status' => 'publish',
    'ignore_sticky_posts' => true,
];

if ($category_id) {
    $query_args['cat'] = $category_id;
}

$query = new WP_Query($query_args);

// Links to the filtered category archive when one is set, otherwise this
// post type's own archive (falling back to the blog page for 'post', which
// doesn't always have a dedicated archive URL of its own) — either way,
// the same items this block is a preview of. A post type with no archive
// page at all (show_ui but no public archive) gets no "view all" link;
// partials.blocks.section-header already skips rendering it when empty.
if ($category_id) {
    $view_all_url = get_category_link($category_id);
    $term = get_term($category_id, 'category');
    $view_all_label = ($term && ! is_wp_error($term))
        ? sprintf(__('View all in %s', 'sage'), $term->name)
        : __('View all posts', 'sage');
} elseif ($post_type === 'post') {
    $blog_page_id = (int) get_option('page_for_posts');
    $view_all_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/');
    $view_all_label = __('View all posts', 'sage');
} else {
    $view_all_url = get_post_type_archive_link($post_type);
    $post_type_object = get_post_type_object($post_type);
    $view_all_label = sprintf(__('View all %s', 'sage'), $post_type_object ? $post_type_object->label : $post_type);
}

$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Recent Posts', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => $view_all_label,
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view('pkde-kit::partials.blocks.post-grid', ['query' => $query, 'cardStyle' => $card_style])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
