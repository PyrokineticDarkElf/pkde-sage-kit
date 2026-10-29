<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Entry Grid block frontend output: a card grid of every published entry of one post type (any registered, public
 * post type, blog posts by default). It reuses the partials every other loop block uses (partials.blocks.post-grid,
 * partials.content), which only call generic template tags, so they work for any post type.
 *
 * This is a listing, not a "latest N" teaser like Recent Posts: a count of 0 means every published entry, ordered by
 * each entry's Page Attributes order (post types that support it) and then title, and there is no "View all" link.
 *
 * @var array $attributes
 */

$post_type = sanitize_key($attributes['postType'] ?? 'post');
if (! post_type_exists($post_type) || ! is_post_type_viewable($post_type)) {
    $post_type = 'post';
}
$count = (int) ($attributes['count'] ?? 0);

$query = new WP_Query([
    'post_type' => $post_type,
    'post_status' => 'publish',
    'posts_per_page' => $count > 0 ? $count : -1,
    'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'ignore_sticky_posts' => true,
]);

$post_type_object = get_post_type_object($post_type);
$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => \App\pkde_loop_theme_classes($theme),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php if (! empty($attributes['heading']) || ! empty($attributes['description'])) : ?>
        <?php echo view('pkde-kit::partials.blocks.section-header', [
            'heading' => $attributes['heading'] ?? '',
            'description' => $attributes['description'] ?? '',
            'viewAllUrl' => null,
            'viewAllLabel' => '',
            'themed' => (bool) $theme,
        ])->render(); ?>
    <?php endif; ?>
    <?php echo view('pkde-kit::partials.blocks.post-grid', [
        'query' => $query,
        'cardStyle' => $card_style,
        'emptyMessage' => sprintf(
            /* translators: %s: post type plural label, e.g. "Projects" */
            __('No %s published yet.', 'sage'),
            $post_type_object ? strtolower($post_type_object->labels->name) : $post_type,
        ),
    ])->render(); ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
