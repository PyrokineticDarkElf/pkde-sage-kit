<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * One feature of a feature grid (WP-100). An icon (drawn in the section's icon colour) or a picture, a heading, a text, an optional link.
 *
 * @var array $attributes
 */

$link_url = (string) ($attributes['linkUrl'] ?? '');
$link_text = (string) ($attributes['linkText'] ?? '');
$image_id = (int) ($attributes['imageId'] ?? 0);
$image_url = (string) ($attributes['imageUrl'] ?? '');

echo view('pkde-kit::partials.blocks.feature-item', [
    'icon' => $image_id || $image_url || ! empty($attributes['placeholder']) ? '' : \App\feature_icon((string) ($attributes['icon'] ?? '')),
    'image' => $image_id || $image_url || ! empty($attributes['placeholder']) ? \App\block_media($image_id, $image_url, (string) ($attributes['imageAlt'] ?? ''), 'medium_large', ['loading' => 'lazy'], ! empty($attributes['placeholder'])) : '',
    'heading' => (string) ($attributes['heading'] ?? ''),
    'text' => (string) ($attributes['text'] ?? ''),
    'linkText' => $link_text,
    'linkUrl' => $link_url,
])->render();
