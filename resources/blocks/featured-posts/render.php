<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Featured Posts block frontend output.
 *
 * @var array $attributes
 */

$source = $attributes['source'] ?? 'sticky';
$count = max(1, (int) ($attributes['count'] ?? 3));
$post_ids_raw = $attributes['postIds'] ?? '';

$query_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
];

if ($source === 'manual') {
    $post_ids = array_filter(array_map('intval', explode(',', $post_ids_raw)));

    if (empty($post_ids)) {
        $query_args['post__in'] = [0]; // No matches — deliberately empty rather than falling back to "all posts".
    } else {
        $query_args['post__in'] = $post_ids;
        $query_args['orderby'] = 'post__in';
        $query_args['posts_per_page'] = count($post_ids);
    }
} else {
    $sticky = get_option('sticky_posts');

    if (empty($sticky)) {
        $query_args['post__in'] = [0];
    } else {
        $query_args['post__in'] = $sticky;
        $query_args['orderby'] = 'post__in';
        $query_args['posts_per_page'] = $count;
    }
}

$query = new WP_Query($query_args);

// Neither "sticky" nor "manual" has its own natural archive URL (WordPress
// has no dedicated "featured posts" listing), so this always links to the
// blog page — the closest honest "see more posts like these".
$blog_page_id = (int) get_option('page_for_posts');
$view_all_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/');

$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Featured Posts', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $view_all_url,
        'viewAllLabel' => __('View all posts', 'sage'),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo view('pkde-kit::partials.blocks.post-grid', [
        'query' => $query,
        'cardStyle' => $card_style,
        'emptyMessage' => $source === 'manual'
            ? __('No posts selected yet.', 'sage')
            : __('No posts are marked as featured yet — stick a post to the front page from its own editor to feature it here.', 'sage'),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
