<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * CTA block frontend output. Two styles share this one block rather than
 * being separate blocks (docs/planning/block-ideas.md's original "Banner
 * CTA" and "Section CTA" were near-identical — same fields, different
 * layout only) — see block.json's "style" attribute.
 *
 * @var array $attributes
 */

$style = $attributes['style'] ?? 'section';
$is_banner = $style === 'banner';
$theme = sanitize_html_class($attributes['theme'] ?? '');
$heading = $attributes['heading'] ?? '';
$content = $attributes['content'] ?? '';
$primary_text = $attributes['primaryButtonText'] ?? '';
$primary_url = $attributes['primaryButtonUrl'] ?? '';
$secondary_text = $attributes['secondaryButtonText'] ?? '';
$secondary_url = $attributes['secondaryButtonUrl'] ?? '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim(implode(' ', array_filter([
        'pkde-section rounded-4xl px-6 py-10 sm:px-10 lg:px-16',
        $is_banner
            ? 'flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between lg:py-8'
            : 'py-16 text-center lg:py-20',
        $theme ? "theme-{$theme}" : '',
    ]))),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="<?php echo $is_banner ? 'min-w-0' : 'mx-auto max-w-2xl'; ?>">
        <?php if ($heading) : ?>
            <h2 class="font-display text-2xl font-semibold leading-tight tracking-[-0.03em] sm:text-3xl"><?php echo wp_kses_post($heading); ?></h2>
        <?php endif; ?>

        <?php if ($content) : ?>
            <p class="mt-3 text-base leading-7 opacity-75"><?php echo wp_kses_post($content); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($primary_text || $secondary_text) : ?>
        <div class="<?php echo $is_banner ? 'flex flex-none flex-wrap gap-3' : 'mt-8 flex flex-wrap justify-center gap-3'; ?>">
            <?php if ($primary_text && $primary_url) : ?>
                <a href="<?php echo esc_url($primary_url); ?>" class="pkde-btn"><?php echo esc_html($primary_text); ?></a>
            <?php endif; ?>
            <?php if ($secondary_text && $secondary_url) : ?>
                <a href="<?php echo esc_url($secondary_url); ?>" class="pkde-btn-alt"><?php echo esc_html($secondary_text); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
