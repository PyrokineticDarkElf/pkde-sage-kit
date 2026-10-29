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
    <?php echo \App\block_image($image_id, $image_url, $image_alt, 'full', ['class' => 'absolute inset-0 -z-20 h-full w-full object-cover', 'sizes' => '100vw']); ?>
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
            <?php if ($heading) : ?>
                <h2 class="font-display text-4xl font-semibold leading-[1.05] tracking-[-0.04em] sm:text-5xl lg:text-6xl"><?php echo wp_kses_post($heading); ?></h2>
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
