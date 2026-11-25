<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Text and image block (WP-100): the text side is InnerBlocks ($content), the picture side one image or a three-picture mosaic.
 *
 * @var array $attributes
 * @var string $content the rendered inner blocks
 */

$image_id = (int) ($attributes['imageId'] ?? 0);
$image_url = (string) ($attributes['imageUrl'] ?? '');
$extra = array_slice(array_values(array_filter((array) ($attributes['extraImages'] ?? []), fn($image) => is_array($image) && (! empty($image['url']) || ! empty($image['id'])))), 0, 2);
$ratio = in_array($attributes['imageRatio'] ?? '4-3', ['4-3', 'square', 'portrait', 'wide', 'auto'], true) ? $attributes['imageRatio'] : '4-3';
$side = ($attributes['imageSide'] ?? 'right') === 'left' ? 'left' : 'right';

$picture = fn(int $id, string $url, string $alt) => \App\block_image($id, $url, $alt, 'large', ['loading' => 'lazy', 'sizes' => '(min-width: 1024px) 50vw, 100vw']);
$images = [];

if ($image_id || $image_url) {
    $images[] = $picture($image_id, $image_url, (string) ($attributes['imageAlt'] ?? ''));
    foreach ($extra as $image) {
        $images[] = $picture((int) ($image['id'] ?? 0), (string) ($image['url'] ?? ''), (string) ($image['alt'] ?? ''));
    }
}

// A pattern's empty picture slot: one frame, or the three of the mosaic, until the editor chooses pictures (WP-108 B).
if ($images === [] && ! empty($attributes['placeholder'])) {
    $frame = '<span class="pkde-placeholder" role="img" aria-label="' . esc_attr__('Picture placeholder: choose a picture', 'sage') . '"></span>';
    $images = array_fill(0, (int) $attributes['placeholder'] === 3 ? 3 : 1, $frame);
}

echo view('pkde-kit::partials.blocks.text-and-image', [
    'wrapper' => get_block_wrapper_attributes(['class' => trim("pkde-block pkde-tai pkde-tai--ratio-{$ratio}" . ($side === 'left' ? ' pkde-split--media-left' : ''))]),
    'eyebrow' => (string) ($attributes['eyebrow'] ?? ''),
    'content' => $content,
    'images' => $images,
    'mosaic' => count($images) === 3,
])->render();
