<?php

/**
 * Theme filters.
 */

namespace App;

// Loaded by Composer's autoloader (autoload.files), also outside WordPress (the kit's own tests, Composer scripts): only the pure
// functions of helpers.php are wanted there.
if (! function_exists('add_action')) {
    return;
}

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Keep editorial archive grids in complete responsive rows (12 divides
 * evenly into 2, 3, and 4-column layouts, so no size leaves a dangling
 * partial row). Sticky posts work against this: WordPress pins a sticky
 * post to page 1 as an *extra* item whenever it falls outside the normal
 * date-ordered top 12, silently making the count 13. Featuring a post is
 * already handled on its own terms by the Featured Posts block ("stick to
 * the front page"), so the archive grid doesn't need WordPress's native
 * sticky-post injection on top of that.
 */
add_action('pre_get_posts', function ($query) {
    // is_home() covers the blog listing itself when a static front page is
    // set (Settings > Reading > "Posts page") — that query is is_home(), not
    // is_archive(), so checking only is_archive() would silently skip the
    // main blog grid entirely and leave it exposed to sticky-post injection.
    $isEditorialListing = $query->is_home() || $query->is_archive();

    if (! is_admin() && $query->is_main_query() && $isEditorialListing && ! $query->is_post_type_archive('product') && ! $query->is_tax(['product_cat', 'product_tag', 'product_brand'])) {
        $query->set('posts_per_page', 12);
        $query->set('ignore_sticky_posts', true);
    }
});

/**
 * Every taxonomy term on a single post/product becomes a body class
 * (`{taxonomy}-{term-slug}`, e.g. `post_tag-topic-one`, `product_cat-category-two`)
 * so CSS/JS can target "posts tagged X" or "products in category Y" without
 * extra markup. is_single() covers WooCommerce's product pages too (any
 * public, non-page post type, not just `post`) — deliberate, since a
 * product's category/tag/brand/attribute terms are exactly the kind of
 * thing worth targeting this way. Only genuinely public taxonomies are
 * included (get_object_taxonomies(..., 'objects') + ->public), so internal
 * ones a plugin registers for its own bookkeeping (e.g. WooCommerce's
 * product_shipping_class) don't add meaningless noise.
 */
add_filter('body_class', function ($classes) {
    if (! is_single()) {
        return $classes;
    }

    $post_id = get_queried_object_id();
    $taxonomies = get_object_taxonomies(get_post_type($post_id), 'objects');

    foreach ($taxonomies as $taxonomy) {
        if (! $taxonomy->public || $taxonomy->name === 'post_format') {
            continue;
        }

        $terms = get_the_terms($post_id, $taxonomy->name);

        if (! $terms || is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            $classes[] = sanitize_html_class("{$taxonomy->name}-{$term->slug}");
        }
    }

    return $classes;
});

/**
 * Drop WordPress's emoji detection script and styles (wp-emoji-release.js on every page). Browsers
 * render emoji natively; the script only swaps in images for very old ones.
 */
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
});

/**
 * Opt-in: drop WordPress's block-library, global-styles and classic-theme stylesheets (about 165 KB of render-blocking
 * CSS) on a site whose pages are built from Blade templates rather than block content. Off by default, so a block-built
 * site is unaffected; a Blade-only site turns it on in its own code:
 *
 *   add_filter('pkde_dequeue_block_styles', '__return_true');
 *
 * Bring the styles back (return false) if a page ever uses block content. Runs on `wp`, i.e. front end only.
 */
add_action('wp', function () {
    if (! apply_filters('pkde_dequeue_block_styles', false)) {
        return;
    }

    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    add_action('wp_enqueue_scripts', function () {
        foreach (['wp-block-library', 'global-styles', 'classic-theme-styles'] as $handle) {
            wp_dequeue_style($handle);
        }
    }, 100);
});
