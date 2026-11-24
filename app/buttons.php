<?php

/**
 * The global button system in WordPress (WP-109, `buttons` in config/brand-palette.json). The CSS and the variables are generated; this file is the PHP side, and exists only
 * for a site that declares `buttons` (resources/generated/buttons.php):
 *  - the core Button block's styles are Solid (the default), Outline and Text, and an Editor picks the style only: no colours, size or radius (an Administrator keeps the
 *    full controls);
 *  - helpers for the kit's own blocks: App\button_class('primary') is the class every kit button carries.
 */

namespace App;

/** The generated declaration, or null for a site that declares no buttons. */
function buttons_declaration(): ?array
{
    $file = function_exists('get_theme_file_path') ? get_theme_file_path('resources/generated/buttons.php') : '';

    return $file !== '' && is_file($file) ? (array) require $file : null;
}

/**
 * The classes of a kit block's button for a "Button style" choice: primary (`pkde-btn`), secondary (`pkde-btn-alt`) or text (`pkde-btn pkde-btn--text`). Anything else is primary.
 * Without `buttons` in the palette each is drawn from the section's button roles; with it, from the one declaration.
 */
function button_classes(string $variant = 'primary'): string
{
    return match ($variant) {
        'secondary' => 'pkde-btn-alt',
        'text' => 'pkde-btn pkde-btn--text',
        default => 'pkde-btn',
    };
}

/** The class of a button variant: primary, secondary or text. Anything else is primary. */
function button_class(string $variant = 'primary'): string
{
    return 'pkde-btn pkde-btn--' . (in_array($variant, ['primary', 'secondary', 'text'], true) ? $variant : 'primary');
}

/**
 * The block styles of core/button for a site that declares buttons: Solid (the core "fill"), Outline, Text.
 *
 * @param  list<array<string, mixed>>  $styles
 * @return list<array<string, mixed>>
 */
function button_block_styles(array $styles): array
{
    $names = array_column($styles, 'name');
    $out = [];

    foreach ($styles as $style) {
        if (($style['name'] ?? '') === 'fill') {
            $style['label'] = 'Solid';
        }
        $out[] = $style;
    }

    foreach (['fill' => 'Solid', 'outline' => 'Outline'] as $name => $label) {
        if (! in_array($name, $names, true)) {
            $out[] = ['name' => $name, 'label' => $label] + ($name === 'fill' ? ['isDefault' => true] : []);
        }
    }

    if (! in_array('text', $names, true)) {
        $out[] = ['name' => 'text', 'label' => 'Text'];
    }

    return $out;
}

/**
 * The theme.json fragment that takes the colour, size, border and spacing controls off the core Button block for someone who cannot manage options.
 *
 * @return array<string, mixed>
 */
function button_editor_restrictions(): array
{
    return ['settings' => ['blocks' => ['core/button' => [
        'color' => ['text' => false, 'background' => false, 'custom' => false, 'customGradient' => false, 'defaultGradients' => false, 'defaultPalette' => false, 'palette' => ['theme' => []]],
        'typography' => ['customFontSize' => false, 'fontFamily' => false, 'fontStyle' => false, 'fontWeight' => false, 'letterSpacing' => false, 'textTransform' => false, 'lineHeight' => false],
        'border' => ['radius' => false, 'color' => false, 'width' => false, 'style' => false],
        'spacing' => ['padding' => false, 'margin' => false],
        'shadow' => ['defaultPresets' => false],
    ]]]];
}

if (! function_exists('add_filter')) {
    return; // outside WordPress (the unit tests load this file for the functions above)
}

add_filter('register_block_type_args', function (array $args, string $name): array {
    if ($name === 'core/button' && buttons_declaration() !== null) {
        $args['styles'] = button_block_styles((array) ($args['styles'] ?? []));
    }

    return $args;
}, 10, 2);

add_filter('wp_theme_json_data_theme', function ($data) {
    if (buttons_declaration() === null || (function_exists('current_user_can') && current_user_can('manage_options'))) {
        return $data;
    }

    $version = (int) ($data->get_data()['version'] ?? 3);

    return $data->update_with(['version' => $version] + button_editor_restrictions());
}, 30);
