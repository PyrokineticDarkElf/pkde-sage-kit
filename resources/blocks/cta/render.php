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
$slot_id = (int) ($attributes['imageId'] ?? 0);
$slot_url = (string) ($attributes['imageUrl'] ?? '');
// A logo or picture beside (banner) or above (section) the text: a plain image that keeps its shape, never cropped.
$slot = ($slot_id || $slot_url || ! empty($attributes['placeholder'])) ? \App\block_media($slot_id, $slot_url, (string) ($attributes['imageAlt'] ?? ''), 'medium', ['loading' => 'lazy', 'class' => 'pkde-cta-slot' . ($is_banner ? '' : ' mx-auto mb-6')], ! empty($attributes['placeholder'])) : '';

// The `link` layout: no heading, no text and no button, one bold uppercase link with a circled chevron, centred. A slim band when it sits alone in a Section (sections.css).
if ($style === 'link') {
    if ($primary_text !== '' && $primary_url !== '') {
        echo '<div ' . get_block_wrapper_attributes(['class' => 'pkde-block pkde-cta pkde-cta--link']) . '><a class="pkde-cta__link" href="' . esc_url($primary_url) . '">'
            . '<span>' . esc_html($primary_text) . '</span>'
            . '<svg class="pkde-cta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" /><path d="M10.5 8l4 4-4 4" /></svg>'
            . '</a></div>';
    }

    return;
}

// The `picture` layout: a rounded card with a picture behind the text, under a tint of the section's colour: the heading, a line of text and one button, left aligned.
if ($style === 'picture') {
    $picture = ($slot_id || $slot_url || ! empty($attributes['placeholder']))
        ? \App\block_media($slot_id, $slot_url, (string) ($attributes['imageAlt'] ?? ''), 'large', ['loading' => 'lazy', 'class' => 'pkde-cta__img'], ! empty($attributes['placeholder']))
        : '';
    echo '<div ' . get_block_wrapper_attributes(['class' => 'pkde-block pkde-cta pkde-cta--picture' . ($theme ? " theme-{$theme}" : '')]) . '>'
        . ($picture !== '' ? '<div class="pkde-cta__bg" aria-hidden="true">' . $picture . '</div>' : '')
        . '<div class="pkde-cta__text">'
        . ($heading !== '' ? '<h2 class="pkde-cta__title">' . wp_kses_post($heading) . '</h2>' : '')
        . ($content !== '' ? '<p class="pkde-cta__line">' . wp_kses_post($content) . '</p>' : '')
        . (($primary_text !== '' && $primary_url !== '') ? '<div class="pkde-actions"><a href="' . esc_url($primary_url) . '" class="' . esc_attr(\App\button_classes((string) ($attributes['buttonStyle'] ?? 'primary'))) . ' no-underline">' . esc_html($primary_text) . '</a></div>' : '')
        . '</div></div>';

    return;
}

// The `promo` layout: a slim band in one row: a badge, the title, one button and a small picture. The heading is the title; no supporting text.
if ($style === 'promo') {
    $badge = trim((string) ($attributes['badge'] ?? ''));
    $picture = ($slot_id || $slot_url || ! empty($attributes['placeholder']))
        ? \App\block_media($slot_id, $slot_url, (string) ($attributes['imageAlt'] ?? ''), 'medium_large', ['loading' => 'lazy', 'class' => 'pkde-cta__img'], ! empty($attributes['placeholder']))
        : '';
    echo '<div ' . get_block_wrapper_attributes(['class' => 'pkde-block pkde-cta pkde-cta--promo' . ($theme ? " theme-{$theme}" : '')]) . '>'
        . '<div class="pkde-cta__text">'
        . ($badge !== '' ? '<span class="pkde-cta__badge">' . esc_html($badge) . '</span>' : '')
        . ($heading !== '' ? '<h2 class="pkde-cta__title">' . wp_kses_post($heading) . '</h2>' : '')
        . '</div>'
        . (($primary_text !== '' && $primary_url !== '') ? '<a href="' . esc_url($primary_url) . '" class="' . esc_attr(\App\button_classes((string) ($attributes['buttonStyle'] ?? 'primary'))) . ' pkde-cta__button no-underline">' . esc_html($primary_text) . '</a>' : '')
        . ($picture !== '' ? '<div class="pkde-cta__media">' . $picture . '</div>' : '')
        . '</div>';

    return;
}

// Two more layouts: `split` (the text beside a picture that fills its half of the panel and takes the panel's corners) and `boxed` (a card in the section, in the
// section's card colours). The picture of a split is a real photograph that is cropped, not the logo of the banner.
if (in_array($style, ['split', 'boxed'], true)) {
    $buttons = '';
    foreach ([['primary', $primary_text, $primary_url, (string) ($attributes['buttonStyle'] ?? 'primary')], ['secondary', $secondary_text, $secondary_url, (string) ($attributes['secondaryButtonStyle'] ?? 'secondary')]] as [$which, $text, $url, $variant]) {
        if ($text && $url) {
            $buttons .= '<a href="' . esc_url($url) . '" class="' . esc_attr(\App\button_classes($variant)) . ' no-underline">' . esc_html($text) . '</a>';
        }
    }
    $picture = '';
    if ($style === 'split') {
        $picture = \App\block_media($slot_id, $slot_url, (string) ($attributes['imageAlt'] ?? ''), 'large', ['loading' => 'lazy', 'class' => 'pkde-cta__img'], true);
    }
    echo view('pkde-kit::partials.blocks.cta-panel', [
        'wrapper' => get_block_wrapper_attributes(['class' => trim("pkde-block pkde-cta pkde-cta--{$style} pkde-card--flush" . ($theme ? " theme-{$theme}" : ''))]),
        'heading' => (string) $heading,
        'content' => (string) $content,
        'buttons' => $buttons,
        'picture' => $picture,
    ])->render();

    return;
}

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
    <?php if ($slot && $is_banner) {
        echo $slot;
    } ?>
    <div class="<?php echo $is_banner ? 'min-w-0' : 'mx-auto max-w-2xl'; ?>">
        <?php if ($slot && ! $is_banner) {
            echo $slot;
        } ?>
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
                <a href="<?php echo esc_url($primary_url); ?>" class="<?php echo esc_attr(\App\button_classes((string) ($attributes['buttonStyle'] ?? 'primary'))); ?> no-underline"><?php echo esc_html($primary_text); ?></a>
            <?php endif; ?>
            <?php if ($secondary_text && $secondary_url) : ?>
                <a href="<?php echo esc_url($secondary_url); ?>" class="<?php echo esc_attr(\App\button_classes((string) ($attributes['secondaryButtonStyle'] ?? 'secondary'))); ?> no-underline"><?php echo esc_html($secondary_text); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
