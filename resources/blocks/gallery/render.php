<?php

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

$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($heading) : ?>
        <h2 class="font-display text-2xl font-semibold tracking-[-0.02em] text-neutral-950 sm:text-3xl"><?php echo wp_kses_post($heading); ?></h2>
    <?php endif; ?>
    <?php if ($description) : ?>
        <p class="mt-2 max-w-2xl text-neutral-600"><?php echo wp_kses_post($description); ?></p>
    <?php endif; ?>

    <?php if ($images) : ?>
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
        <p class="mt-6 text-center text-neutral-600"><?php esc_html_e('No images selected yet.', 'sage'); ?></p>
    <?php endif; ?>
</div>
