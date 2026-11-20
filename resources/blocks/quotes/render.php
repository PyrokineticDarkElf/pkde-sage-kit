<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Testimonials block (WP-100), registered as sage/quotes: a new name on purpose, because a site (el) already has its own sage/testimonials.
 *
 * @var array $attributes
 */

$layout = in_array($attributes['layout'] ?? 'carousel', ['carousel', 'strip', 'panel'], true) ? $attributes['layout'] : 'carousel';
$quotes = \App\quotes_for($attributes);
$source = $attributes['source'] ?? 'reviews';

if ($quotes === []) {
    // The block editor loads this render over REST: say why the block is empty there (and only there).
    if (defined('REST_REQUEST') && REST_REQUEST) {
        printf(
            '<div %s style="padding:1rem;border:1px dashed currentColor;text-align:center;opacity:.7">%s</div>',
            get_block_wrapper_attributes(['class' => 'pkde-block pkde-quotes-empty']),
            esc_html($source === 'reviews' ? __('Testimonials: there are no approved product reviews at this rating yet, so nothing is shown on the site.', 'sage') : __('Testimonials: nothing to show yet.', 'sage')),
        );
    }

    return;
}

echo view('pkde-kit::partials.blocks.quotes', [
    'wrapper' => get_block_wrapper_attributes(['class' => "pkde-block pkde-quotes pkde-quotes--{$layout}"]),
    'layout' => $layout,
    'eyebrow' => (string) ($attributes['eyebrow'] ?? ''),
    'heading' => (string) ($attributes['heading'] ?? ''),
    'quotes' => $quotes,
    'stars' => ! empty($attributes['showStars']),
])->render();
