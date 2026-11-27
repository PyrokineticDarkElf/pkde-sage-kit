<?php

namespace App;

/**
 * The block settings that move things: "On hover: Lift" and "When scrolled into view: Fade up" (the Animation panel, resources/js/animation.jsx). They live only in the
 * block comment; the page gets the classes here when it renders, and the motion itself is CSS (resources/css/motion.css) plus the small reveal script (resources/js/motion.js).
 * A saved block's HTML never changes, so removing a setting never breaks a block, and the settings saved on any block keep rendering.
 *
 * THE SITE SWITCH `pkde_kit/motion` ('on' default, 'reduced', 'off'), from the site's config (a filter in functions.php or a config file):
 *   on       hover, press and the reveal;
 *   reduced  hover and press, no reveal (no block is given the reveal class);
 *   off      nothing moves (no reveal or lift class either).
 * It is the same for every visitor and lands as a class on <html> (pkde-motion-on, -reduced, -off), so a cached page is never wrong for anyone. The visitor's own
 * prefers-reduced-motion always wins, in the CSS and in the script.
 *
 * GUARD RAILS for the reveal, here and not left to editors: never the hero, a slider or a slide (sage/hero, sage/hero-slider, sage/hero-slide), never the FIRST block of the
 * page content (the LCP and the first paint), and the CSS and script add: nothing already on screen at load, nothing holding the LCP image (fetchpriority="high"), nothing
 * left hidden at the end of the page.
 */

/** Blocks that carry a Reveal on scroll toggle for an Editor (Section and the kit's section blocks). */
const MOTION_SECTION_BLOCKS = [
    'sage/section', 'sage/cta', 'sage/cta-band', 'sage/feature-grid', 'sage/text-and-image', 'sage/quotes', 'sage/product-loop', 'sage/product-categories',
    'sage/entry-grid', 'sage/latest-posts', 'sage/featured-posts', 'sage/faq', 'sage/accordion',
];

/** Blocks that are never revealed. */
const MOTION_NO_REVEAL_BLOCKS = ['sage/hero', 'sage/hero-slider', 'sage/hero-slide'];

function motion_mode_of(mixed $value): string
{
    return in_array($value, ['on', 'reduced', 'off'], true) ? $value : 'on';
}

/** The site's motion mode: on, reduced or off. */
function motion_mode(): string
{
    return motion_mode_of(function_exists('apply_filters') ? apply_filters('pkde_kit/motion', 'on') : 'on');
}

/**
 * The classes a block gets from its animation settings.
 *
 * @param  array<string, mixed>  $attrs  the block's saved attributes
 * @param  bool  $first  the first block of the page content
 * @return list<string>
 */
function motion_classes(array $attrs, string $blockName, bool $first, string $mode): array
{
    $classes = [];
    $clean = fn(string $value) => function_exists('sanitize_html_class') ? sanitize_html_class($value) : preg_replace('/[^A-Za-z0-9_-]/', '', $value);

    if (! empty($attrs['animationHover']) && $mode !== 'off') {
        $classes[] = 'pkde-hover-' . $clean((string) $attrs['animationHover']);
    }

    if (! empty($attrs['animationEntrance']) && $mode === 'on' && ! $first && ! in_array($blockName, MOTION_NO_REVEAL_BLOCKS, true)) {
        $classes[] = 'pkde-reveal'; // "fade-up", the only value, is the reveal; older saved content keeps working
    }

    return $classes;
}

/** The class that goes on <html>. */
function motion_html_class(string $mode): string
{
    return 'pkde-motion-' . motion_mode_of($mode);
}

/** Add a class to the opening tag of a language_attributes() string, whether or not it already has a class attribute. */
function motion_add_html_class(string $attributes, string $class): string
{
    if (preg_match('/\bclass="([^"]*)"/', $attributes)) {
        return (string) preg_replace('/\bclass="([^"]*)"/', 'class="$1 ' . $class . '"', $attributes, 1);
    }

    return trim($attributes . ' class="' . $class . '"');
}

if (! function_exists('add_filter')) {
    return;
}

/** $GLOBALS['pkde_motion']: whether the main page content is being rendered, how deep in blocks we are, and how many top-level blocks it has rendered (the first one is never revealed). */
$GLOBALS['pkde_motion'] = ['content' => false, 'depth' => 0, 'top' => 0];

add_filter('the_content', function ($content) {
    $GLOBALS['pkde_motion'] = ['content' => function_exists('in_the_loop') && in_the_loop() && is_main_query(), 'depth' => 0, 'top' => 0];

    return $content;
}, 8);

add_filter('the_content', function ($content) {
    $GLOBALS['pkde_motion']['content'] = false;

    return $content;
}, 12);

add_filter('pre_render_block', function ($pre) {
    $GLOBALS['pkde_motion']['depth']++;

    return $pre;
}, 1);

add_filter('render_block', function ($html, $block) {
    $state = &$GLOBALS['pkde_motion'];
    $state['depth'] = max(0, $state['depth'] - 1);
    $first = false;

    if ($state['content'] && $state['depth'] === 0) {
        $first = ++$state['top'] === 1;
    }

    $classes = motion_classes((array) ($block['attrs'] ?? []), (string) ($block['blockName'] ?? ''), $first, motion_mode());
    $tags = new \WP_HTML_Tag_Processor($html);

    if (! $classes || ! $tags->next_tag()) {
        return $html;
    }

    foreach ($classes as $class) {
        $tags->add_class($class);
    }

    return $tags->get_updated_html();
}, 10, 2);

// the site's switch, as a class on <html>: the same for every visitor
add_filter('language_attributes', fn($output) => motion_add_html_class((string) $output, motion_html_class(motion_mode())), 10);

// the editor: the mode (the toggle is hidden when it is not 'on') and who gets the full Animation panel (Administrators; `pkde_kit/motion_panel` changes that)
add_filter('block_editor_settings_all', function ($settings) {
    $settings['pkdeMotion'] = motion_mode();
    $settings['pkdeMotionPanel'] = (bool) apply_filters('pkde_kit/motion_panel', current_user_can('manage_options'));
    $settings['pkdeMotionBlocks'] = MOTION_SECTION_BLOCKS;

    return $settings;
});
