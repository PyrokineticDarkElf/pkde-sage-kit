<?php

/**
 * A site's default card look for the loop blocks (Product Loop, Recent Posts). A block with no look of its own takes the site's; a block that names one keeps it, and
 * `plain` on a block means "the standard card, not the site's default". Sections and patterns therefore point at a card by setting the block's Card look; the site's
 * default is one small file in the theme, `config/pkde-cards.json`: {"product-loop": "tiles", "latest-posts": "panel"} (the pattern library's add helper writes it), or the filter
 * `pkde_kit/card_look_default` (string $look, string $block).
 */

namespace App;

/**
 * The card looks a site file names, by block. Anything that is not a block name and a text value is ignored.
 *
 * @return array<string, string>
 */
function card_defaults_read(string $file): array
{
    $data = $file !== '' && is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

    return is_array($data) ? array_filter($data, fn($look, $block) => is_string($block) && is_string($look), ARRAY_FILTER_USE_BOTH) : [];
}

/** The site's default card look for a block ('' when it has none). */
function card_default(string $block): string
{
    static $defaults = null;
    $defaults ??= card_defaults_read(function_exists('get_theme_file_path') ? get_theme_file_path('config/pkde-cards.json') : '');
    $look = (string) ($defaults[$block] ?? '');

    return function_exists('apply_filters') ? (string) apply_filters('pkde_kit/card_look_default', $look, $block) : $look;
}

/**
 * The card look a block shows: its own when it names one of $allowed, '' for `plain` (the standard card), else the site's default when that is one of $allowed, else ''.
 *
 * @param  list<string>  $allowed
 */
function card_look_for(string $block, string $explicit, array $allowed): string
{
    if ($explicit === 'plain') {
        return '';
    }

    if (in_array($explicit, $allowed, true)) {
        return $explicit;
    }

    $default = card_default($block);

    return in_array($default, $allowed, true) ? $default : '';
}
