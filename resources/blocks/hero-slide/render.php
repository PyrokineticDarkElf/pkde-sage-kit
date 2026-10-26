<?php

/**
 * Hero Slide block frontend output — one slide inside a Hero Slider,
 * visually identical to the standalone Hero block (see hero/render.php for
 * the full rationale behind this markup) minus rounded-4xl — a slide sits
 * flush against its neighbours as one continuous strip once the slider
 * wraps it, so rounded corners would only show (oddly) at the very first
 * and last slide — plus flex-none so it behaves as an Embla track item
 * once its parent wraps it (resources/js/carousel.js).
 *
 * @var array $attributes
 */

$heading = $attributes['heading'] ?? '';
// Exactly one slide of a page (usually the first) should carry the page's real <h1>: a slider home page has no page header
// of its own, so without this it would have no <h1> at all. Editable per slide, since which slide is first can change.
$heading_level = in_array($attributes['headingLevel'] ?? 'h2', ['h1', 'h2', 'h3', 'h4'], true) ? $attributes['headingLevel'] : 'h2';
$body = $attributes['content'] ?? '';
$button_text = $attributes['buttonText'] ?? '';
$button_url = $attributes['buttonUrl'] ?? '';
$image_url = $attributes['imageUrl'] ?? '';
$image_alt = $attributes['imageAlt'] ?? '';
// Stored URLs are root-relative, which EWWW's WebP rewriter ignores; \App\block_image() uses the
// attachment (absolute URL plus srcset) when there is one, otherwise makes the stored URL absolute.
$image_id = (int) ($attributes['imageId'] ?? 0);
$theme = sanitize_html_class($attributes['theme'] ?? '');

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('relative isolate flex min-h-[420px] w-full flex-none flex-col justify-end overflow-hidden pkde-section py-10 sm:py-14 lg:min-h-[480px] lg:py-16 ' . ($theme ? "theme-{$theme}" : '')),
]);
?>
<?php
// The first slide is the page's LCP image: eager and high priority. Later slides sit off-screen in the slider track,
// so they are lazy and low priority and stop competing with it for bandwidth. The number comes from a named
// per-request function: a `static` here would reset for every slide (each render.php include has a fresh scope).
$slide_attributes = ['class' => 'absolute inset-0 -z-20 h-full w-full object-cover', 'sizes' => '100vw'];
$slide_attributes += \App\next_hero_slide_number() === 1
    ? ['loading' => 'eager', 'fetchpriority' => 'high']
    : ['loading' => 'lazy', 'fetchpriority' => 'low'];
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\block_image($image_id, $image_url, $image_alt, 'full', $slide_attributes); ?>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-neutral-950 via-neutral-950/55 to-neutral-950/10"></div>

    <div class="<?php echo esc_attr(\App\container_class('mx-auto w-full max-w-content px-[var(--page-gutter,1.5rem)]', 'sage/hero-slide')); ?>">
        <div class="max-w-2xl">
            <?php if ($heading) : ?>
                <<?php echo $heading_level; ?> class="font-display text-3xl font-semibold leading-[1.05] tracking-[-0.04em] sm:text-4xl lg:text-5xl"><?php echo wp_kses_post($heading); ?></<?php echo $heading_level; ?>>
            <?php endif; ?>

            <?php if ($body) : ?>
                <p class="mt-5 max-w-xl text-base leading-7 opacity-75 sm:text-lg"><?php echo wp_kses_post($body); ?></p>
            <?php endif; ?>

            <?php if ($button_text && $button_url) : ?>
                <a href="<?php echo esc_url($button_url); ?>" class="pkde-btn mt-8">
                    <?php echo esc_html($button_text); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
