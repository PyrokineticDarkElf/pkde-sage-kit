<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Image Gallery block frontend output.
 *
 * @var array $attributes
 */

$heading = $attributes['heading'] ?? '';
$description = $attributes['description'] ?? '';
$images = $attributes['images'] ?? [];
$columns = max(2, min(6, (int) ($attributes['columns'] ?? 3)));
$aspect_ratio = $attributes['aspectRatio'] ?? 'video';
$enable_lightbox = ! empty($attributes['enableLightbox']);
$lightbox_group = wp_unique_id('sage-gallery-');

// Tailwind's build only generates classes it can find as literal text in a
// scanned source file — a runtime-interpolated string like
// "lg:grid-cols-{$columns}" would never match anything, since the scanner
// doesn't evaluate PHP. This match() gives it every literal class it needs
// to see once, so whichever one actually gets used at runtime already has
// real CSS behind it.
$column_class = match ($columns) {
    2 => 'lg:grid-cols-2',
    3 => 'lg:grid-cols-3',
    4 => 'lg:grid-cols-4',
    5 => 'lg:grid-cols-5',
    6 => 'lg:grid-cols-6',
    default => 'lg:grid-cols-3',
};

$aspect_class = match ($aspect_ratio) {
    'square' => 'aspect-square',
    '4-3' => 'aspect-[4/3]',
    default => 'aspect-video',
};

$placeholders = ! $images ? max(0, min(12, (int) ($attributes['placeholders'] ?? 0))) : 0;
$contain = ($attributes['fit'] ?? 'cover') === 'contain';
// An optional "View all" tab under the pictures (the block's link text, address and tab setting); pinned, it hangs from the Section's bottom edge
$tab = in_array($attributes['linkTab'] ?? '', ['tab', 'pinned'], true) && trim((string) ($attributes['linkText'] ?? '')) !== '' && trim((string) ($attributes['linkUrl'] ?? '')) !== '';
$row = ! empty($attributes['row']);
$classes = trim(($contain ? 'pkde-gallery--contain' : '') . ($row ? ' pkde-gallery--row' : '') . (! empty($attributes['flush']) ? ' pkde-gallery--flush' : '') . (! empty($attributes['centered']) ? ' pkde-gallery--centered' : '') . ($tab && $attributes['linkTab'] === 'pinned' ? ' has-pinned-tab' : ''));
$wrapper_args = $classes !== '' ? ['class' => $classes] : [];
$row && $wrapper_args['style'] = '--pkde-gallery-cols:' . $columns;
$wrapper_attributes = get_block_wrapper_attributes($wrapper_args);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($heading) : ?>
        <h2 class="font-display text-2xl font-semibold tracking-[-0.02em] text-[color:var(--section-heading,var(--color-neutral-950))] sm:text-3xl"><?php echo wp_kses_post($heading); ?></h2>
    <?php endif; ?>
    <?php if ($description) : ?>
        <p class="mt-2 max-w-2xl text-[color:var(--section-muted,var(--color-neutral-600))]"><?php echo wp_kses_post($description); ?></p>
    <?php endif; ?>

    <?php ob_start(); ?>
    <?php if ($placeholders) : ?>
        <div class="<?php echo ($heading || $description) ? 'mt-6 ' : ''; ?>grid grid-cols-2 gap-4 sm:grid-cols-3 <?php echo $column_class; ?>">
            <?php for ($i = 0; $i < $placeholders; $i++) : ?>
                <div class="<?php echo $aspect_class; ?> overflow-hidden rounded-2xl"><span class="pkde-placeholder" role="img" aria-label="<?php esc_attr_e('Picture placeholder: choose a picture', 'sage'); ?>"></span></div>
            <?php endfor; ?>
        </div>
    <?php elseif ($images) : ?>
        <div class="<?php echo ($heading || $description) ? 'mt-6 ' : ''; ?>grid grid-cols-2 gap-4 sm:grid-cols-3 <?php echo $column_class; ?>">
            <?php foreach ($images as $image) : ?>
                <?php
                // Alt text baked into the block when the page was created can be empty; fall back to the
                // attachment's own alt text so anything an editor adds in the media library shows up.
                $image_alt = $image['alt'] ?? '';
                if ($image_alt === '' && ! empty($image['id'])) {
                    $image_alt = (string) get_post_meta((int) $image['id'], '_wp_attachment_image_alt', true);
                }

                // Stored URLs are root-relative, which EWWW's WebP rewriter ignores: the helpers use the
                // attachment (absolute URL, srcset) where there is one, else make the stored URL absolute.
                $image_id = (int) ($image['id'] ?? 0);
                $full_url = \App\block_image_url($image_id, $image['url'] ?? '');
                ?>
                <div class="<?php echo $aspect_class; ?> overflow-hidden rounded-2xl bg-neutral-100">
                    <?php if ($enable_lightbox) : ?>
                        <button
                            type="button"
                            data-lightbox-trigger
                            data-lightbox-src="<?php echo esc_url($full_url); ?>"
                            data-lightbox-alt="<?php echo esc_attr($image_alt); ?>"
                            data-lightbox-group="<?php echo esc_attr($lightbox_group); ?>"
                            aria-label="<?php esc_attr_e('Enlarge image', 'sage'); ?>"
                            class="block h-full w-full cursor-zoom-in"
                        >
                    <?php endif; ?>
                            <?php echo \App\block_image($image_id, $image['url'] ?? '', $image_alt, 'large', ['loading' => 'lazy', 'class' => 'h-full w-full object-cover']); ?>
                    <?php if ($enable_lightbox) : ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="mt-6 text-center text-[color:var(--section-muted,var(--color-neutral-600))]"><?php esc_html_e('No images selected yet.', 'sage'); ?></p>
    <?php endif; ?>
    <?php
    $pictures = (string) ob_get_clean();

if ($row && str_contains($pictures, '<div class="')) {
    // The row that loops is the same carousel as the other rows (resources/js/carousel.js): a viewport around the grid, the grid its track. It goes round, and moves by itself when Autoplay is on.
    $pictures = '<div class="pkde-gallery__carousel" data-carousel data-carousel-loop="true" data-carousel-autoplay="' . (! empty($attributes['autoplay']) ? 'true' : 'false') . '" data-carousel-autoplay-delay="3000"><div class="pkde-gallery__viewport" data-carousel-viewport>'
        . preg_replace('/<div class="((?:mt-6 )?)grid /', '<div data-carousel-track class="$1grid ', $pictures, 1) . '</div>'
        . (! empty($attributes['autoplay']) ? '<div class="pkde-features__controls"><button type="button" class="pkde-features__pause" data-carousel-autoplay-toggle data-label-pause="' . esc_attr__('Pause the slideshow', 'sage') . '" data-label-play="' . esc_attr__('Start the slideshow', 'sage') . '" aria-label="' . esc_attr__('Pause the slideshow', 'sage') . '"><span class="pkde-features__pause-pause" aria-hidden="true">&#10074;&#10074;</span><span class="pkde-features__pause-play" aria-hidden="true">&#9654;</span></button></div>' : '')
        . '</div>';
}

echo $pictures;
?>
    <?php if ($tab) {
        echo view('pkde-kit::partials.blocks.view-all-tab', ['url' => esc_url_raw($attributes['linkUrl']), 'label' => $attributes['linkText']])->render();
    } ?>
</div>
