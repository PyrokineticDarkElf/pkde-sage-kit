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

add_action('init', function () {
    foreach (section_themes() as $theme) {
        foreach (section_theme_blocks() as $block) {
            register_block_style($block, ['name' => $theme['name'], 'label' => $theme['label']]);
        }
    }
});
