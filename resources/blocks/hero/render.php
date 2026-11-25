<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Hero block frontend output.
 *
 * @var array $attributes
 * @var string $content
 * @var WP_Block $block
 */

$heading = $attributes['heading'] ?? '';
$body = $attributes['content'] ?? '';
$button_text = $attributes['buttonText'] ?? '';
$button_url = $attributes['buttonUrl'] ?? '';
$image_url = $attributes['imageUrl'] ?? '';
$image_alt = $attributes['imageAlt'] ?? '';
// Stored URLs are root-relative, which EWWW's WebP rewriter ignores; \App\block_image() uses the
// attachment (absolute URL plus srcset) when there is one, otherwise makes the stored URL absolute.
$image_id = (int) ($attributes['imageId'] ?? 0);
$theme = sanitize_html_class($attributes['theme'] ?? '');

// Split and text-only layouts (WP-100): colours come from the Brand section theme the hero sits in (no panel of its own), the markup from a view.
// The default layout, the full-width image with its overlay, is the markup below, unchanged.
$layout = in_array($attributes['layout'] ?? 'overlay', ['split', 'text'], true) ? $attributes['layout'] : 'overlay';

if ($layout !== 'overlay') {
    $image_side = ($attributes['imageSide'] ?? 'right') === 'left' ? 'left' : 'right';
    // "contain" shows the whole picture at its own proportions (a cut-out on a transparent background); the default fills the half and crops.
    $image_fit = ($attributes['imageFit'] ?? 'cover') === 'contain' ? ' pkde-hero--fit-contain' : '';
    $hero_image = '';

    if ($layout === 'split' && ($image_id || $image_url || ! empty($attributes['placeholder']))) {
        $first = \App\next_hero_slide_number() === 1;
        $hero_image = \App\block_media($image_id, $image_url, $image_alt, 'large', ['sizes' => '(min-width: 1024px) 50vw, 100vw'] + ($first ? ['loading' => 'eager', 'fetchpriority' => 'high'] : ['loading' => 'lazy']), ! empty($attributes['placeholder']));
    }

    echo view('pkde-kit::partials.blocks.hero-layout', [
        'wrapper' => get_block_wrapper_attributes(['class' => trim('pkde-block pkde-hero pkde-hero--' . $layout . ($layout === 'split' && $image_side === 'left' ? ' pkde-split--media-left' : '') . ($layout === 'split' ? $image_fit : ''))]),
        'layout' => $layout,
        'eyebrow' => (string) ($attributes['eyebrow'] ?? ''),
        'heading' => $heading,
        'level' => max(1, min(4, (int) ($attributes['headingLevel'] ?? 2))),
        'body' => $body,
        'buttonText' => $button_text,
        'buttonUrl' => $button_url,
        'buttonStyle' => (string) ($attributes['buttonStyle'] ?? 'primary'),
        'secondaryButtonStyle' => (string) ($attributes['secondaryButtonStyle'] ?? 'secondary'),
        'secondaryText' => (string) ($attributes['secondaryButtonText'] ?? ''),
        'secondaryUrl' => (string) ($attributes['secondaryButtonUrl'] ?? ''),
        'image' => $hero_image,
    ])->render();

    return;
}

// .pkde-section + the :root --pkde-theme-* defaults in app.css reproduce
// this block's original hardcoded bg-neutral-950/text-white look exactly
// when no theme is picked — adding a theme-{name}/theme-{alias} class (see
// generated/section-themes.css) is what actually re-skins it.
//
// No horizontal padding here on purpose — it used to carry its own
// px-6/sm:px-10/lg:px-16 scale, which positioned this block's content at a
// different X than every other section on the page (confirmed by measuring
// both: this block's text landed 24px left of page-header.blade.php's, on
// otherwise-identical pages). Horizontal spacing now lives solely on the
// inner content wrapper below, using the same flat padding-inline (via
// max-w-content's own convention) as everything else — vertical padding
// stays here since that's still this panel's own concern.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('relative isolate flex min-h-[480px] flex-col justify-end overflow-hidden rounded-4xl pkde-section py-10 sm:py-14 lg:min-h-[560px] lg:py-16 ' . ($theme ? "theme-{$theme}" : '')),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php
    // The first hero (or slider slide) on the page is its LCP image: eager and high priority (WP-91). The counter is shared with the Hero Slider.
    $hero_attributes = ['class' => 'absolute inset-0 -z-20 h-full w-full object-cover', 'sizes' => '100vw'];
$hero_attributes += \App\next_hero_slide_number() === 1 ? ['loading' => 'eager', 'fetchpriority' => 'high'] : ['loading' => 'lazy'];
echo \App\block_image($image_id, $image_url, $image_alt, 'full', $hero_attributes);

if (! $image_id && ! $image_url && ! empty($attributes['placeholder'])) {
    echo '<span class="pkde-placeholder pkde-placeholder--cover" role="img" aria-label="' . esc_attr__('Picture placeholder: choose a picture', 'sage') . '"></span>';
}
?>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-neutral-950 via-neutral-950/55 to-neutral-950/10"></div>

    <?php /*
     * alignfull only breaks the *background* out to the viewport edge — the
     * content itself should still line up with the rest of the site's
     * content column (mx-auto max-w-content px-[var(--page-gutter,1.5rem)], the same width+padding
     * page-header.blade.php and every other full-width section center on),
     * not just sit at whatever this section's own edge padding happens to
     * be. Without this wrapper the heading/text/button drift noticeably out
     * of line with every other section's content on wide screens.
     */ ?>
    <div class="<?php echo esc_attr(\App\container_class('mx-auto w-full max-w-content px-[var(--page-gutter,1.5rem)]', 'sage/hero')); ?>">
        <div class="max-w-2xl">
            <?php $overlay_level = max(1, min(4, (int) ($attributes['headingLevel'] ?? 2))); ?>
            <?php if ($heading) : ?>
                <h<?php echo $overlay_level; ?> class="font-display text-4xl font-semibold leading-[1.05] tracking-[-0.04em] sm:text-5xl lg:text-6xl"><?php echo wp_kses_post($heading); ?></h<?php echo $overlay_level; ?>>
            <?php endif; ?>

            <?php if ($body) : ?>
                <p class="mt-5 max-w-xl text-base leading-7 opacity-75 sm:text-lg"><?php echo wp_kses_post($body); ?></p>
            <?php endif; ?>

            <?php $overlay_second = ((string) ($attributes['secondaryButtonText'] ?? '')) !== '' && ((string) ($attributes['secondaryButtonUrl'] ?? '')) !== ''; ?>
            <?php if (($button_text && $button_url) || $overlay_second) : ?>
                <div class="pkde-actions">
                    <?php if ($button_text && $button_url) : ?>
                        <a href="<?php echo esc_url($button_url); ?>" class="<?php echo esc_attr(\App\button_classes((string) ($attributes['buttonStyle'] ?? 'primary'))); ?> no-underline">
                            <?php echo esc_html($button_text); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($overlay_second) : ?>
                        <a href="<?php echo esc_url((string) $attributes['secondaryButtonUrl']); ?>" class="<?php echo esc_attr(\App\button_classes((string) ($attributes['secondaryButtonStyle'] ?? 'secondary'))); ?> no-underline">
                            <?php echo esc_html((string) $attributes['secondaryButtonText']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
