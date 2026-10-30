<?php

/**
 * Block style variations (an outline button, any `is-style-*`) with block assets loaded on demand (the WordPress 6.9+ default for classic themes).
 *
 * The bug (el, EL-13): WordPress renders the content before `wp_enqueue_scripts` when it loads block assets on demand, so a block with a style
 * variation has already registered the `block-style-variation-styles` handle, which depends on `global-styles`. Core enqueues that handle at
 * `wp_enqueue_scripts` priority 1. For a classic theme the real `global-styles` is registered later, in `wp_footer` (priority 1), and hoisted
 * into the head afterwards; in the head only a placeholder exists. So the enqueue names a dependency that is not registered yet and WordPress
 * raises `_doing_it_wrong` ("... enqueued with dependencies that are not registered: global-styles"), which Acorn turns into an error page in
 * development. Registering `global-styles` early is not the fix: the head prints the empty handle as a dependency and the real global styles are
 * then never printed (el tried it).
 *
 * The fix keeps the on-demand loading (it is what keeps the layout shift at 0, TCS-87; never put `should_load_separate_core_block_assets` back to
 * false): for a classic theme loading on demand, the enqueue moves from `wp_enqueue_scripts` priority 1 to `wp_footer` priority 2, after
 * `global-styles` has been registered (footer priority 1). The variation styles are then printed with the other late styles and hoisted into the
 * head with them, after the global styles, so the cascade is unchanged.
 */

namespace App;

/** True when core's early enqueue of the variation styles must move to the footer: a classic theme with block assets loaded on demand. */
function block_variation_styles_in_footer(bool $isBlockTheme, bool $loadOnDemand): bool
{
    return ! $isBlockTheme && $loadOnDemand;
}

if (! function_exists('add_action')) {
    return;
}

add_action('wp_enqueue_scripts', function () {
    $onDemand = function_exists('wp_should_load_block_assets_on_demand') ? wp_should_load_block_assets_on_demand() : wp_should_load_separate_core_block_assets();

    if (block_variation_styles_in_footer(wp_is_block_theme(), $onDemand) && has_action('wp_enqueue_scripts', 'wp_enqueue_block_style_variation_styles')) {
        remove_action('wp_enqueue_scripts', 'wp_enqueue_block_style_variation_styles', 1);
        add_action('wp_footer', 'wp_enqueue_block_style_variation_styles', 2);
    }
}, 0);
