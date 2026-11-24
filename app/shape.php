<?php

/**
 * Shape tokens in theme.json (WP-108 A): the corner radius and border thickness the palette declares (`shape` in config/brand-palette.json, generated into
 * resources/generated/shape.php) become theme.json custom settings (`--wp--custom--pkde--radius--md` and so on) and the radius of core's buttons, so the block editor and the
 * frontend follow the same three radius steps as the kit's CSS. Nothing here restyles anything else: images, covers and groups keep whatever the site's own theme.json says.
 */

namespace App;

/**
 * The theme.json fragment for a shape.
 *
 * @param  array{preset?: string, radius?: array<string, string>, border?: array<string, string>}  $shape
 * @return array<string, mixed>
 */
function shape_theme_json(array $shape): array
{
    return [
        'settings' => ['custom' => ['pkde' => ['radius' => (array) ($shape['radius'] ?? []), 'border' => (array) ($shape['border'] ?? [])]]],
        'styles' => ['elements' => ['button' => ['border' => ['radius' => 'var(--pkde-radius-full)']]]],
    ];
}

if (! function_exists('add_filter')) {
    return; // outside WordPress (the unit tests load this file for the function above)
}

add_filter('wp_theme_json_data_theme', function ($data) {
    $file = function_exists('get_theme_file_path') ? get_theme_file_path('resources/generated/shape.php') : '';

    if ($file === '' || ! is_file($file)) {
        return $data;
    }

    $shape = (array) require $file;
    $version = (int) ($data->get_data()['version'] ?? 3);

    return $data->update_with(['version' => $version] + shape_theme_json($shape));
}, 20);
