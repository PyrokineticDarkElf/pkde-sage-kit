<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Hero Slider block frontend output. Wraps its already-rendered Hero Slide
 * children (each one flex-none, full width, via hero-slide/render.php) in
 * the same Embla track + arrow markup the Image Carousel block uses — both
 * share resources/js/carousel.js's data-carousel controller. See that
 * file's own comment for why Embla (not hand-rolled CSS scroll-snap)
 * drives this. No track spacing here (unlike Carousel's own -ml-4/pl-4
 * pair) — full-width slides sit flush edge to edge, so there's no gap to
 * get out of sync with Embla's own distance math in the first place.
 * Pagination dots overlay the bottom of the slide itself (absolutely
 * positioned, not laid out below it) rather than sitting in normal flow
 * underneath — a slide is a full hero section, not a small thumbnail, so
 * "below the slide" would just be a lot of empty page between it and the
 * next section.
 *
 * @var array $attributes
 * @var string $content
 * @var WP_Block $block
 */

$slide_count = count(array_filter(
    $block->parsed_block['innerBlocks'] ?? [],
    fn($inner_block) => ($inner_block['blockName'] ?? '') === 'sage/hero-slide',
));

$show_arrows = ! empty($attributes['showArrows']) && $slide_count > 1;
$show_dots = ! empty($attributes['showDots']) && $slide_count > 1;
$loop = ! empty($attributes['loop']);
$autoplay = ! empty($attributes['autoplay']) && $slide_count > 1;
$autoplay_delay = max(1000, (int) ($attributes['autoplayDelay'] ?? 5000));

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'relative']);
?>
<div <?php echo $wrapper_attributes; ?>>
    <div
        data-carousel
        data-carousel-loop="<?php echo $loop ? 'true' : 'false'; ?>"
        data-carousel-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
        data-carousel-autoplay-delay="<?php echo esc_attr($autoplay_delay); ?>"
        class="relative"
    >
        <div data-carousel-viewport class="cursor-grab overflow-hidden active:cursor-grabbing">
            <div data-carousel-track class="flex">
                <?php echo $content; ?>
            </div>
        </div>

        <?php if ($autoplay) : ?>
            <button type="button" data-carousel-autoplay-toggle data-label-pause="<?php esc_attr_e('Pause slideshow', 'sage'); ?>" data-label-play="<?php esc_attr_e('Start slideshow', 'sage'); ?>" aria-label="<?php esc_attr_e('Pause slideshow', 'sage'); ?>" aria-pressed="false" class="group absolute bottom-3 left-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-neutral-900 shadow transition hover:bg-white sm:bottom-5 sm:left-5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 group-data-[paused]:hidden" aria-hidden="true"><path d="M6 4h3v12H6zM11 4h3v12h-3z" /></svg>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="hidden h-4 w-4 group-data-[paused]:block" aria-hidden="true"><path d="M6 4l10 6-10 6z" /></svg>
            </button>
        <?php endif; ?>

        <?php if ($show_arrows) : ?>
            <button type="button" data-carousel-prev aria-label="<?php esc_attr_e('Previous slide', 'sage'); ?>" class="absolute left-4 top-1/2 -mt-5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-neutral-900 shadow-lg transition hover:bg-neutral-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.24a.75.75 0 010-1.06l4.25-4.24a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
            </button>
            <button type="button" data-carousel-next aria-label="<?php esc_attr_e('Next slide', 'sage'); ?>" class="absolute right-4 top-1/2 -mt-5 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-neutral-900 shadow-lg transition hover:bg-neutral-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" /></svg>
            </button>
        <?php endif; ?>

        <?php if ($show_dots) : ?>
            <div class="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center sm:bottom-4">
                <?php for ($index = 0; $index < $slide_count; $index++) : ?>
                    <?php // The button is the 24px touch target (WCAG 2.2 target size); the span inside is the visible dot.?>
                    <button type="button" data-carousel-dot aria-label="<?php echo esc_attr(sprintf(__('Go to slide %d', 'sage'), $index + 1)); ?>" class="group pointer-events-auto flex h-6 min-w-6 items-center justify-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-white"><span class="block h-2 w-2 rounded-full bg-white/40 transition-all group-data-[active]:w-5 group-data-[active]:bg-white"></span></button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
