<?php

/**
 * Image Carousel block frontend output. The track/drag/loop/snap mechanics
 * are handled by Embla (resources/js/carousel.js) — see that file's own
 * comment for why this moved off the earlier hand-rolled CSS-scroll-snap
 * version (no real loop, imprecise jump distance, no drag). The optional
 * lightbox on each slide is resources/js/lightbox.js, a separate hand-rolled
 * (no library) overlay — see that file's own comment.
 *
 * Slide spacing deliberately isn't a CSS `gap` on the track — Embla's own
 * distance math (how far to move the track per slide/drag) is driven by
 * each slide element's own measured box, and `gap` isn't part of that,
 * so a `gap`-spaced track and Embla's own idea of "one slide's width"
 * silently drift apart, clipping part of the incoming slide on every
 * scroll. This uses Embla's own documented spacing pattern instead: a
 * negative margin on the track (`-ml-4`) plus matching padding on every
 * slide (`pl-4`) — spacing that's genuinely part of each slide's own
 * measured box, so Embla's math and the visible gap always agree.
 *
 * @var array $attributes
 */

$heading = $attributes['heading'] ?? '';
$description = $attributes['description'] ?? '';
$images = $attributes['images'] ?? [];
$aspect_ratio = $attributes['aspectRatio'] ?? 'video';
$show_arrows = ! empty($attributes['showArrows']) && count($images) > 1;
$show_dots = ! empty($attributes['showDots']) && count($images) > 1;
$loop = ! empty($attributes['loop']);
$autoplay = ! empty($attributes['autoplay']) && count($images) > 1;
$autoplay_delay = max(1000, (int) ($attributes['autoplayDelay'] ?? 5000));
$enable_lightbox = ! empty($attributes['enableLightbox']);
$lightbox_group = wp_unique_id('sage-carousel-');

// Same literal-class gotcha as the Gallery block — see gallery/render.php.
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
        <div
            data-carousel
            data-carousel-loop="<?php echo $loop ? 'true' : 'false'; ?>"
            data-carousel-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
            data-carousel-autoplay-delay="<?php echo esc_attr($autoplay_delay); ?>"
            class="<?php echo ($heading || $description) ? 'mt-6 ' : ''; ?>relative"
        >
            <div data-carousel-viewport class="cursor-grab overflow-hidden active:cursor-grabbing">
                <div data-carousel-track class="flex -ml-4">
                    <?php foreach ($images as $image) : ?>
                        <?php
                        // Root-relative stored URLs are ignored by EWWW's WebP rewriter; see the gallery block.
                        $image_id = (int) ($image['id'] ?? 0);
                        $full_url = \App\block_image_url($image_id, $image['url'] ?? '');
                        ?>
                        <div class="w-[85%] flex-none pl-4 sm:w-2/3 lg:w-1/2">
                            <div class="<?php echo $aspect_class; ?> overflow-hidden rounded-2xl bg-neutral-100">
                                <?php if ($enable_lightbox) : ?>
                                    <button
                                        type="button"
                                        data-lightbox-trigger
                                        data-lightbox-src="<?php echo esc_url($full_url); ?>"
                                        data-lightbox-alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                                        data-lightbox-group="<?php echo esc_attr($lightbox_group); ?>"
                                        aria-label="<?php esc_attr_e('Enlarge image', 'sage'); ?>"
                                        class="block h-full w-full cursor-zoom-in"
                                    >
                                <?php endif; ?>
                                <?php echo \App\block_image($image_id, $image['url'] ?? '', $image['alt'] ?? '', 'large', ['loading' => 'lazy', 'class' => 'h-full w-full object-cover', 'draggable' => 'false']); ?>
                                <?php if ($enable_lightbox) : ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($autoplay) : ?>
                <button type="button" data-carousel-autoplay-toggle data-label-pause="<?php esc_attr_e('Pause slideshow', 'sage'); ?>" data-label-play="<?php esc_attr_e('Start slideshow', 'sage'); ?>" aria-label="<?php esc_attr_e('Pause slideshow', 'sage'); ?>" aria-pressed="false" class="group absolute bottom-3 left-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-neutral-900 shadow transition hover:bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 group-data-[paused]:hidden" aria-hidden="true"><path d="M6 4h3v12H6zM11 4h3v12h-3z" /></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="hidden h-4 w-4 group-data-[paused]:block" aria-hidden="true"><path d="M6 4l10 6-10 6z" /></svg>
                </button>
            <?php endif; ?>

            <?php if ($show_arrows) : ?>
                <button type="button" data-carousel-prev aria-label="<?php esc_attr_e('Previous slide', 'sage'); ?>" class="absolute left-2 top-1/2 -mt-5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-neutral-900 shadow-lg transition hover:bg-neutral-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.24a.75.75 0 010-1.06l4.25-4.24a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
                </button>
                <button type="button" data-carousel-next aria-label="<?php esc_attr_e('Next slide', 'sage'); ?>" class="absolute right-2 top-1/2 -mt-5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-neutral-900 shadow-lg transition hover:bg-neutral-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" /></svg>
                </button>
            <?php endif; ?>

            <?php if ($show_dots) : ?>
                <div class="mt-2 flex justify-center">
                    <?php foreach ($images as $index => $image) : ?>
                        <?php // The button is the 24px touch target (WCAG 2.2 target size); the span inside is the visible dot.?>
                        <button type="button" data-carousel-dot aria-label="<?php echo esc_attr(sprintf(__('Go to slide %d', 'sage'), $index + 1)); ?>" class="group flex h-6 min-w-6 items-center justify-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-neutral-900"><span class="block h-2 w-2 rounded-full bg-neutral-300 transition-all group-data-[active]:w-5 group-data-[active]:bg-neutral-900"></span></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p class="mt-6 text-center text-neutral-600"><?php esc_html_e('No images selected yet.', 'sage'); ?></p>
    <?php endif; ?>
</div>
