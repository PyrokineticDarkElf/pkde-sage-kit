<?php

/**
 * Helpers for the section blocks: the global blocks' declaration file, the small icon set of the feature grid, and the
 * `.pkde-themed` marker a kit block adds when it carries a Brand section theme itself.
 */

namespace App;

/**
 * The site's root folder (the one holding config/, web/ and composer.json). Bedrock puts WordPress in web/wp/ and content in web/app/, so the
 * root is two levels above either. A site with another layout sets it with the filter.
 */
function site_root(): string
{
    $root = defined('WP_CONTENT_DIR') ? dirname((string) constant('WP_CONTENT_DIR'), 2) : (defined('ABSPATH') ? dirname((string) constant('ABSPATH'), 2) : '');

    return function_exists('apply_filters') ? (string) apply_filters('pkde_kit/site_root', $root) : $root;
}

/**
 * The declaration of a global block (tier 3): config/pkde-sections.php in the site root returns one array per block, for example
 * `['cta-band' => ['heading' => '...', 'text' => '...', 'buttonText' => '...', 'buttonUrl' => '...', 'image' => '...', 'theme' => 'brand-slate']]`.
 * Empty when the file or the entry is missing. The page holds no copy of this content: change the file and every page that carries the block changes.
 *
 * @return array<string,mixed>
 */
function section_declaration(string $name, ?string $file = null): array
{
    static $cache = [];

    $file ??= site_root() . '/config/pkde-sections.php';

    if ($file === '' || ! is_file($file)) {
        return [];
    }

    $all = $cache[$file] ??= (array) require $file;
    $entry = $all[$name] ?? [];

    return is_array($entry) ? $entry : [];
}

/** The icons a feature item can use: key => the inner SVG of a 24x24 stroke icon (drawn in currentColor, so a section theme's icon colour applies). */
function feature_icons(): array
{
    return [
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10Z"/><path d="M2 21c0-3 1.9-5.4 5-6.4"/>',
        'heart' => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.7a1 1 0 0 0-.2-.6l-3.4-4.3a1 1 0 0 0-.8-.4H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'sparkles' => '<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2Z"/><path d="M19 3v4M17 5h4"/>',
    ];
}

/** An icon as inline SVG ('' for an unknown key). Decorative: hidden from assistive technology, the item's heading carries the meaning. */
function feature_icon(string $key, string $class = 'pkde-icon'): string
{
    $icon = feature_icons()[$key] ?? null;

    return $icon === null ? '' : sprintf('<svg class="%s" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>', esc_attr_safe($class), $icon);
}

/** esc_attr outside WordPress (the kit's own tests): the same escaping for the characters that matter. */
function esc_attr_safe(string $value): string
{
    return function_exists('esc_attr') ? esc_attr($value) : htmlspecialchars($value, ENT_QUOTES);
}

if (! function_exists('add_filter')) {
    return;
}

// The feature grid's icon set for the editor (the same SVG the page prints), so the block's icon picker draws what the page will show.
add_filter('block_editor_settings_all', function ($settings) {
    $settings['pkdeIcons'] = array_map(fn($key) => feature_icon($key), array_keys(feature_icons()));
    $settings['pkdeIcons'] = array_combine(array_keys(feature_icons()), $settings['pkdeIcons']);

    return $settings;
});

/**
 * The quotes a Quotes block shows, from its source, as a list of ['quote' => html-safe text, 'name' => string, 'role' => string, 'rating' => 0..5].
 *   reviews: approved WooCommerce product reviews at or above a rating, the same words only once (one customer often reviews two products with the same
 *            text); the "role" is the product name;
 *   posts:   a post type (the testimonial CPT of the sites that have one): the title is the name, the content the quote, the meta `role` and `rating`;
 *   manual:  the items typed into the block.
 *
 * @param  array<string,mixed>  $attributes
 * @return list<array{quote:string,name:string,role:string,rating:int}>
 */
function quotes_for(array $attributes): array
{
    $count = max(1, min(24, (int) ($attributes['count'] ?? 6)));
    $source = in_array($attributes['source'] ?? 'reviews', ['reviews', 'posts', 'manual'], true) ? $attributes['source'] : 'reviews';
    $list = [];

    if ($source === 'manual') {
        foreach ((array) ($attributes['items'] ?? []) as $item) {
            if (is_array($item) && trim((string) ($item['quote'] ?? '')) !== '') {
                $list[] = ['quote' => (string) $item['quote'], 'name' => (string) ($item['name'] ?? ''), 'role' => (string) ($item['role'] ?? ''), 'rating' => max(0, min(5, (int) ($item['rating'] ?? 0)))];
            }
        }

        return array_slice($list, 0, $count);
    }

    if ($source === 'posts') {
        $type = sanitize_key((string) ($attributes['postType'] ?? 'testimonial'));

        foreach (post_type_exists($type) ? get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => $count, 'orderby' => 'date', 'order' => 'DESC']) : [] as $post) {
            $quote = trim(wp_strip_all_tags(get_the_excerpt($post)));
            if ($quote !== '') {
                $list[] = ['quote' => $quote, 'name' => get_the_title($post), 'role' => (string) get_post_meta($post->ID, 'role', true), 'rating' => max(0, min(5, (int) get_post_meta($post->ID, 'rating', true)))];
            }
        }

        return $list;
    }

    if (! class_exists('WooCommerce')) {
        return [];
    }

    $min = max(1, min(5, (int) ($attributes['minRating'] ?? 4)));
    $seen = [];

    foreach (get_comments(['type' => 'review', 'status' => 'approve', 'number' => $count * 3, 'orderby' => 'comment_date_gmt', 'order' => 'DESC', 'meta_query' => [['key' => 'rating', 'value' => $min, 'compare' => '>=', 'type' => 'NUMERIC']]]) as $review) {
        $key = md5($review->comment_author . '|' . $review->comment_content);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $list[] = ['quote' => wp_strip_all_tags($review->comment_content), 'name' => (string) $review->comment_author, 'role' => (string) get_the_title((int) $review->comment_post_ID), 'rating' => (int) get_comment_meta($review->comment_ID, 'rating', true)];
    }

    return array_slice($list, 0, $count);
}

/**
 * The generated section theme that belongs to a colour's ROLE (brand, accent, black, white) in a mode (solid or light), so a pattern works on every
 * site whatever its colours are called. '' when the palette has none (then the pattern's section is simply unthemed).
 */
function section_theme_for(string $role, string $mode = 'solid', ?array $themes = null): string
{
    foreach ($themes ?? section_themes() as $theme) {
        if (($theme['role'] ?? null) === $role && ($theme['mode'] ?? '') === $mode) {
            return (string) $theme['name'];
        }
    }

    return '';
}

/** The opening and closing markup of a pattern's locked Section: the content may be edited, the structure may not. */
function pattern_section(string $role, string $mode, string $inner, ?array $themes = null): string
{
    $theme = section_theme_for($role, $mode, $themes);
    $class = $theme !== '' ? 'is-style-' . $theme : '';
    $attrs = '{' . ($class !== '' ? '"className":"' . $class . '",' : '') . '"templateLock":"contentOnly"}';

    return "<!-- wp:sage/section {$attrs} -->\n<div class=\"wp-block-sage-section alignfull" . ($class !== '' ? " {$class}" : '') . "\">\n{$inner}\n</div>\n<!-- /wp:sage/section -->\n";
}

/** The swatch slugs a card button may use (the theme's generated editor-palettes.php, the Button block's list). */
function card_button_slugs(?string $file = null): array
{
    $file ??= function_exists('get_theme_file_path') ? get_theme_file_path('resources/generated/editor-palettes.php') : '';

    return $file !== '' && is_file($file) ? array_values(array_filter((array) (((array) require $file)['button'] ?? []), 'is_string')) : [];
}

/**
 * The class and inline style a block with cards adds to its wrapper for its "Card colour" and "Card button colour" settings:
 *   cardTheme  '' (Auto: the cards follow the section they sit in) or a Brand theme id: `has-card-theme-<id>` re-themes only the cards from that theme's own matrix;
 *   cardButton '' or a swatch slug from the Button list: the card button's colour, its label the readable foreground token of that swatch.
 * Anything not in the generated lists is ignored (a stored value never reaches the page unchecked).
 *
 * @param  array<string,mixed>  $attributes
 * @return array{class: string, style: string}
 */
function card_wrapper_args(array $attributes, string $class = '', string $style = '', ?array $themes = null, ?array $buttons = null): array
{
    $theme = (string) ($attributes['cardTheme'] ?? '');
    $classes = [trim($class)];

    if ($theme !== '' && in_array($theme, array_column($themes ?? section_themes(), 'name'), true)) {
        $classes[] = "has-card-theme-{$theme}";
    }

    $slug = (string) ($attributes['cardButton'] ?? '');
    $styles = [trim($style)];

    if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug) && in_array($slug, $buttons ?? card_button_slugs(), true)) {
        $neutral = ['white' => 'black', 'black' => 'white'][$slug] ?? null;
        $background = $neutral !== null ? "var(--color-{$slug}-exact)" : "var(--color-{$slug})";
        $label = $neutral !== null ? "var(--color-{$neutral}-exact)" : "var(--color-{$slug}-fg)";
        $styles[] = "--section-card-button-bg: {$background}; --section-card-button-text: {$label}; --section-card-button-border: {$background};";
    }

    return ['class' => trim(implode(' ', array_filter($classes))), 'style' => trim(implode(' ', array_filter($styles)))];
}
