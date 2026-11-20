<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Feature grid (WP-100): the heading and intro are attributes, the items are InnerBlocks (sage/feature-item, rendered into $content).
 *
 * @var array $attributes
 * @var string $content
 */

$columns = max(1, min(5, (int) ($attributes['columns'] ?? 3)));
$variant = ($attributes['variant'] ?? 'plain') === 'cards' ? 'cards' : 'plain';
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
])->render();
