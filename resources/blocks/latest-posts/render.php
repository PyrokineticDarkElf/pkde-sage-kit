<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Latest Posts block frontend output.
 *
 * @var array $attributes
 */

// Where the items come from: the most recent (default), the sticky posts (blog posts only), or a listing of every entry in the
// order of its Order field then title (a count of 0 means all, and there is no "View all" link: the grid is the whole list).
$source = in_array($attributes['source'] ?? 'latest', ['latest', 'sticky', 'manual'], true) ? $attributes['source'] : 'latest';
$count = (int) ($attributes['count'] ?? 3);
$count = $source === 'manual' && $count <= 0 ? -1 : max(1, $count);
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

if ($source === 'manual') {
    $query_args['orderby'] = ['menu_order' => 'ASC', 'title' => 'ASC'];
} elseif ($source === 'sticky' && $post_type === 'post') {
    // no sticky posts means nothing to show, not "everything"
    $query_args['post__in'] = array_map('intval', (array) get_option('sticky_posts')) ?: [0];
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

// a listing says what it lists when empty (the same words the Entry Grid block had); the recent and sticky grids keep the partial's default
$empty_message = null;

if ($source === 'manual') {
    $view_all_url = null;
    $post_type_object = get_post_type_object($post_type);
    /* translators: %s: post type plural label, e.g. "Projects" */
    $empty_message = sprintf(__('No %s published yet.', 'sage'), $post_type_object ? strtolower($post_type_object->labels->name) : $post_type);
}

$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');
$posts_layout = in_array($attributes['layout'] ?? 'cards', ['cards', 'feature', 'list', 'panel', 'overlay', 'label', 'rail'], true) ? $attributes['layout'] : 'cards';
// How the posts are arranged (cards in a grid, a row that scrolls, one large post beside a list, a dated list) and, for the grid and the row, which card look they take (panel, overlay,
// label). The older layout values panel, overlay and label still work: they mean the grid with that look.
$arrangement = in_array($posts_layout, ['feature', 'list', 'rail'], true) ? $posts_layout : 'cards';
$look = in_array($arrangement, ['cards', 'rail'], true)
    ? \App\card_look_for('latest-posts', in_array($posts_layout, ['panel', 'overlay', 'label'], true) && ! in_array($attributes['cardLook'] ?? '', ['panel', 'overlay', 'label', 'plain'], true) ? $posts_layout : (string) ($attributes['cardLook'] ?? ''), ['panel', 'overlay', 'label'])
    : '';
// A look puts "View all" under the grid, centred, instead of beside the heading
$view_all_under = $look !== '' && ! empty($view_all_url) && $source !== 'manual';
$header_view_all = $view_all_under ? null : $view_all_url;
// Panel look option: the "View all" tab hangs from the section's bottom edge
$pinned = $view_all_under && ! empty($attributes['pinViewAll']);
$loop_classes = trim(($arrangement !== 'cards' ? " pkde-posts--{$arrangement}" : '') . ($look !== '' ? " pkde-posts--{$look}" : '') . ($pinned ? ' has-pinned-tab' : '') . (! empty($attributes['headingTab']) ? ' has-heading-tab' : ''));
$wrapper_attributes = get_block_wrapper_attributes(\App\card_wrapper_args($attributes, trim(\App\pkde_loop_theme_classes($theme) . ($loop_classes !== '' ? ' pkde-posts ' . $loop_classes : ''))));
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_loop_theme_content_open($theme); ?>
    <?php echo view('pkde-kit::partials.blocks.section-header', [
        'heading' => $attributes['heading'] ?? __('Recent Posts', 'sage'),
        'description' => $attributes['description'] ?? '',
        'viewAllUrl' => $header_view_all,
        'viewAllLabel' => $view_all_label,
        'tab' => ! empty($attributes['headingTab']),
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php
    $grid_html = view('pkde-kit::partials.blocks.post-grid', ['query' => $query, 'cardStyle' => $card_style, 'emptyMessage' => $empty_message])->render();

if ($arrangement === 'rail' && str_contains($grid_html, '<div class="grid')) {
    // The row that scrolls is the same carousel as the People row (resources/js/carousel.js): a viewport around the grid, the grid its track, dots made by the script
    $grid_html = '<div class="pkde-posts__carousel" data-carousel data-carousel-loop="false"><div class="pkde-posts__viewport" data-carousel-viewport>'
        . preg_replace('/<div class="grid/', '<div data-carousel-track class="grid', $grid_html, 1)
        . '</div><div class="pkde-features__controls"><div class="pkde-features__dots" data-carousel-dots data-label="' . esc_attr__('Go to position', 'sage') . '"></div></div></div>';
}

echo $grid_html;
?>
    <?php if ($view_all_under) : ?>
        <?php echo view('pkde-kit::partials.blocks.view-all-tab', ['url' => $view_all_url, 'label' => $look === 'panel' ? __('View all', 'sage') : $view_all_label, 'link' => $look !== 'panel'])->render(); ?>
    <?php endif; ?>
    <?php echo \App\pkde_loop_theme_content_close($theme); ?>
</div>
