<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Feature grid: the heading and intro are attributes, the items are InnerBlocks (sage/feature-item, rendered into $content).
 *
 * @var array $attributes
 * @var string $content
 */

$columns = max(1, min(5, (int) ($attributes['columns'] ?? 3)));
$variant = in_array($attributes['variant'] ?? 'plain', ['plain', 'cards', 'steps', 'stats', 'people'], true) ? $attributes['variant'] : 'plain';
$centered = ! empty($attributes['centered']);

echo view('pkde-kit::partials.blocks.feature-grid', [
    'wrapper' => get_block_wrapper_attributes(\App\card_wrapper_args(
        $attributes,
        trim("pkde-block pkde-features pkde-features--{$variant} pkde-features--cols-{$columns}" . ($centered ? ' pkde-features--centered' : '')),
        "--pkde-features-cols: {$columns};",
    )),
    'eyebrow' => (string) ($attributes['eyebrow'] ?? ''),
    'heading' => (string) ($attributes['heading'] ?? ''),
    'intro' => (string) ($attributes['intro'] ?? ''),
    'content' => $content,
    'linkText' => (string) ($attributes['linkText'] ?? ''),
    'linkUrl' => (string) ($attributes['linkUrl'] ?? ''),
    'scrolls' => $variant === 'people',
    // the People style is the kit's Embla carousel (js/carousel.js): the settings travel as data attributes
    'carousel' => $variant === 'people' ? [
        'loop' => ! empty($attributes['loop']),
        'autoplay' => ! empty($attributes['autoplay']),
        'delay' => max(1000, min(30000, (int) ($attributes['autoplayDelay'] ?? 5000))),
        'duration' => ['slow' => 45, 'normal' => 25, 'fast' => 12][$attributes['speed'] ?? 'normal'] ?? 25,
        'dots' => ($attributes['showDots'] ?? true) !== false,
        'arrows' => ! empty($attributes['showArrows']),
    ] : null,
])->render();
