<?php

/**
 * Section themes as block styles (WP-100): "Brand <Label>" and "Brand <Label> (light)" for every palette colour, offered in the Styles panel of
 * the Group block and of the kit's Section block. The list is generated with the colours (resources/generated/section-themes.php in the theme,
 * written by bin/generate-theme-tokens.mjs); the CSS that paints a section and lets the core blocks inside it follow is resources/css/section-themes.css.
 *
 * A style is a class (`is-style-brand-sage`) on the block, so stored pages keep working when a theme is renamed in the palette: the class is
 * built from the colour's NAME, which never changes; only the label shown in the picker does.
 */

namespace App;

// Loaded by Composer's autoloader (autoload.files), also outside WordPress: only the pure function is wanted there.
function section_themes(?string $file = null): array
{
    static $cache = [];

    $file ??= function_exists('get_theme_file_path') ? get_theme_file_path('resources/generated/section-themes.php') : '';

    if ($file === '' || ! is_file($file)) {
        return [];
    }

    return $cache[$file] ??= array_values(array_filter((array) require $file, fn($row) => is_array($row) && ! empty($row['name']) && ! empty($row['label'])));
}

/** The blocks that can carry a section theme. A site adds its own with the filter. */
function section_theme_blocks(): array
{
    $blocks = ['core/group', 'sage/section'];

    return function_exists('apply_filters') ? array_values((array) apply_filters('pkde_kit/section_theme_blocks', $blocks)) : $blocks;
}

if (! function_exists('add_action')) {
    return;
}

/** The blocks that can be an eyebrow (TPL-281): the small kicker above a heading, in the brand highlight of the section it sits in. */
function eyebrow_blocks(): array
{
    return ['core/paragraph', 'core/heading'];
}

/**
 * Adds `pkde-eyebrow` to a core Paragraph or Heading set to the "Eyebrow" style, so it takes the same look and colour (`--section-eyebrow`) as the eyebrow of the kit's
 * own blocks and the pattern bank. The editor shows `is-style-eyebrow`, which the CSS (resources/css/sections.css) treats the same way.
 */
function eyebrow_class(string $html, array $block): string
{
    if (! in_array($block['blockName'] ?? '', eyebrow_blocks(), true) || ! str_contains((string) ($block['attrs']['className'] ?? ''), 'is-style-eyebrow')) {
        return $html;
    }

    $tags = new \WP_HTML_Tag_Processor($html);

    return $tags->next_tag() && $tags->add_class('pkde-eyebrow') ? $tags->get_updated_html() : $html;
}

add_action('init', function () {
    foreach (eyebrow_blocks() as $block) {
        register_block_style($block, ['name' => 'eyebrow', 'label' => __('Eyebrow', 'sage')]);
    }

    foreach (section_themes() as $theme) {
        foreach (section_theme_blocks() as $block) {
            register_block_style($block, ['name' => $theme['name'], 'label' => $theme['label']]);
        }
    }
});

add_filter('render_block', __NAMESPACE__ . '\\eyebrow_class', 10, 2);
