<?php

/**
 * Native block registration.
 *
 * Every subdirectory under resources/blocks/ containing a block.json is
 * registered automatically. The editor UI (edit.js) for each block is
 * bundled into resources/js/editor.js — see that file to add a new block's
 * import — while the frontend output comes from each block's render.php
 * (referenced by block.json's "render" field), so no separate per-block
 * asset loading is needed.
 */

namespace App;

// Loaded by Composer's autoloader (autoload.files), also outside WordPress (the kit's own tests, Composer scripts): only the pure
// functions of helpers.php are wanted there.
if (! function_exists('add_action')) {
    return;
}

/**
 * Wrapper classes that turn a "loop" block (Recent Posts, Brands, Recent
 * Products, ...) into a themed, full-width section — the same
 * pkde-section/theme-{name} background system Hero and CTA use, plus
 * `pkde-loop-panel`, which forces the coloured background edge to edge
 * (app.css: `.content-grid > .pkde-loop-panel { grid-column: full; }`)
 * regardless of whatever align (wide/full) the editor's own toolbar has set
 * on this block. That's a deliberate, separate class from `alignfull`
 * itself: Gutenberg's own `align` attribute already controls an
 * `alignwide`/`alignfull` class via get_block_wrapper_attributes(), and
 * fighting that by also injecting a literal "alignfull" string here would
 * leave two equal-specificity content-grid rules on the same element,
 * decided only by CSS source order — correct today, but a landmine for
 * whoever edits that stylesheet next. `pkde-loop-panel` is its own,
 * deliberately higher-specificity rule instead, and — unlike a literal
 * "alignfull" class — only applies to loop blocks (Hero/CTA keep
 * respecting whatever align a client actually picks for them). A themed
 * loop block looks like a full-bleed coloured strip, not a padded, rounded
 * floating panel — see pkde_loop_theme_content_open() immediately below
 * for how the actual heading/grid content still lines up with the rest of
 * the site's content column despite the background being unbounded.
 * Returns '' when $theme is empty, so every loop block's render.php can
 * unconditionally append this to its own class list exactly like
 * hero/render.php already does with its own inline theme-{$theme} check —
 * see that file's own comment for the background/foreground contrast
 * rationale this reuses (--pkde-theme-content, --pkde-theme-highlight).
 */
function pkde_loop_theme_classes(string $theme): string
{
    if (! $theme) {
        return '';
    }

    return apply_filters('pkde_kit/section_class', 'pkde-section pkde-loop-panel theme-' . sanitize_html_class($theme) . ' py-10 sm:py-14 lg:py-16', $theme);
}

/**
 * Opening/closing half of the inner content wrapper a themed loop block
 * needs — same two-layer pattern as hero/render.php's own
 * `mx-auto w-full max-w-content px-[var(--page-gutter,1.5rem)]` wrapper (see that file's comment):
 * the *background* is full-bleed (pkde_loop_theme_classes()'s `alignfull`),
 * but the heading/description/"View all" link and the card grid inside
 * still need to line up with every other section's content column, not
 * spill to the true viewport edge. Only needed when actually themed —
 * an unthemed loop block already gets this same horizontal inset for free,
 * from content-grid's own `.content-grid > *:not(.alignfull)` padding rule,
 * since it isn't `alignfull` at all in that case. Returns '' from both when
 * $theme is empty, so every loop block's render.php can echo these
 * unconditionally around its existing section-header + grid output.
 */
function pkde_loop_theme_content_open(string $theme): string
{
    return $theme ? '<div class="' . esc_attr(container_class('mx-auto w-full max-w-content px-[var(--page-gutter,1.5rem)]', 'sage/loop-section')) . '">' : '';
}

function pkde_loop_theme_content_close(string $theme): string
{
    return $theme ? '</div>' : '';
}

/**
 * Validates a loop block's `cardStyle` attribute against the styles
 * card-themes.css actually generates (`buildCardThemesCss()` in
 * bin/generate-theme-tokens.mjs) — 'white' (the plain default, no marker
 * class needed at all), 'tinted', or 'bold'. Falls back to 'white' for
 * anything else, the same defensive pattern sanitize_html_class() already
 * gives `theme` elsewhere in this file, since $attributes ultimately comes
 * from stored block content a client (or a stale/hand-edited block) could
 * have set to something this system no longer recognises.
 */
function pkde_sanitize_card_style(string $cardStyle): string
{
    return in_array($cardStyle, ['white', 'tinted', 'bold'], true) ? $cardStyle : 'white';
}

/**
 * Opening/closing half of a `pkde-card-{style}` wrapper — for a themed
 * block whose "cards" aren't a single element render.php already controls
 * (a loop block's own post/product template), but a genuinely separate
 * child block instance (Accordion Item, inside Accordion/FAQs) that can't
 * receive a PHP prop the way a template include can. `--pkde-card-*` are
 * ordinary CSS custom properties, which cascade to every descendant
 * automatically — so wrapping the *parent* block's inner content in this
 * one marker div is enough for `buildCardThemesCss()`'s generated
 * `.theme-{name} .pkde-card-{style}` rule to set the right values for every
 * Accordion Item inside it, with no per-item coordination needed at all.
 * Returns '' from both for 'white' (the plain default — every --pkde-card-*
 * token already has a :root fallback for exactly this case, see app.css),
 * so a caller can echo these unconditionally regardless of cardStyle.
 */
function pkde_card_style_wrapper_open(string $cardStyle): string
{
    return $cardStyle !== 'white' ? '<div class="pkde-card-' . sanitize_html_class($cardStyle) . '">' : '';
}

function pkde_card_style_wrapper_close(string $cardStyle): string
{
    return $cardStyle !== 'white' ? '</div>' : '';
}

/**
 * Animation settings: every block has two, On hover and When scrolled into view (the Animation panel, see
 * resources/js/animation.jsx). They live only in the block comment; the page gets the classes here when it renders and the motion
 * is CSS in app.css, so a saved block's HTML never changes and removing this never breaks a block.
 */
const PKDE_ANIMATIONS = ['animationHover' => 'pkde-hover-', 'animationEntrance' => 'pkde-entrance-'];

add_filter('register_block_type_args', function ($args) {
    $args['attributes'] = ($args['attributes'] ?? []) + array_fill_keys(array_keys(PKDE_ANIMATIONS), ['type' => 'string']);

    return $args;
});

add_filter('render_block', function ($html, $block) {
    $classes = [];
    foreach (PKDE_ANIMATIONS as $attribute => $prefix) {
        if (! empty($block['attrs'][$attribute])) {
            $classes[] = $prefix . sanitize_html_class($block['attrs'][$attribute]);
        }
    }

    $tags = new \WP_HTML_Tag_Processor($html);
    if (! $classes || ! $tags->next_tag()) {
        return $html;
    }
    foreach ($classes as $class) {
        $tags->add_class($class);
    }

    return $tags->get_updated_html();
}, 10, 2);

add_action('init', function () {
    // The site's own blocks (resources/blocks in the theme) first, then the kit's. A site block with the same directory name as a kit
    // block replaces it: the kit's is skipped, so one name never registers twice.
    $blocks_dirs = array_filter([get_theme_file_path('resources/blocks'), dirname(__DIR__) . '/resources/blocks'], 'is_dir');

    // These blocks' render.php files call WooCommerce functions directly —
    // skip registering them at all when the plugin isn't active, rather than
    // relying on each one's own internal no-op guard to catch it.
    $requires_woocommerce = [
        'product-categories',
        'brands',
        'recent-products',
        'best-selling-products',
        'featured-products',
        'shop-products',
        'product-loop',
    ];

    $registered = [];

    foreach ($blocks_dirs as $blocks_dir) {
        foreach (glob("{$blocks_dir}/*/block.json") as $block_json) {
            $slug = basename(dirname($block_json));

            if (isset($registered[$slug])) {
                continue;
            }

            if (in_array($slug, $requires_woocommerce, true) && ! class_exists('WooCommerce')) {
                continue;
            }

            $registered[$slug] = true;
            register_block_type(dirname($block_json));
        }
    }
});
