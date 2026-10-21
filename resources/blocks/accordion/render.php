<?php

/**
 * Accordion block frontend output. Purely visual — see the FAQs block for
 * the same rendering plus JSON-LD FAQPage structured data; they're kept as
 * two separate blocks (not one with a "is this an FAQ" toggle) because an
 * accordion doesn't imply "these are the site's actual frequently asked
 * questions" the way the FAQs block does. The actual side-by-side/columns
 * layout switch lives in partials/blocks/accordion-layout.blade.php,
 * shared with faq/render.php.
 *
 * Theming follows Hero/CTA's own pattern (a boxed, rounded panel that
 * respects whatever align the editor's own toolbar has set), not the loop
 * blocks' forced-full-bleed one (App\pkde_loop_theme_classes()) — Accordion/
 * FAQs are a single standalone section, not a repeating "loop" of many
 * cards, so a floating panel is the better fit. Accordion Item's own
 * <details>/<summary> can't receive a $themed/$cardStyle prop the way
 * accordion-layout.blade.php's heading/description can below (it's a
 * genuinely separate block instance, not a template include) — see
 * app.css's own `.pkde-section .wp-block-sage-accordion-item` rule (theme)
 * and App\pkde_card_style_wrapper_open()'s own comment (card background)
 * for how it picks up both anyway, purely through CSS scoping/inheritance.
 *
 * @var array $attributes
 * @var string $content
 * @var WP_Block $block
 */

$heading = $attributes['heading'] ?? '';
$description = $attributes['description'] ?? '';
$column_threshold = max(1, (int) ($attributes['columnThreshold'] ?? 6));
$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');

$item_count = count(array_filter(
    $block->parsed_block['innerBlocks'] ?? [],
    fn($inner_block) => ($inner_block['blockName'] ?? '') === 'sage/accordion-item',
));

// Unlike Hero (always a panel, themed or not), Accordion/FAQs keep their
// plain, padding-free look until a theme is actually picked — the
// rounded/padded "panel" treatment is entirely conditional, not just the
// theme-{name} class within it.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $theme ? "rounded-4xl pkde-section theme-{$theme} p-6 sm:p-8 lg:p-10" : '',
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo \App\pkde_card_style_wrapper_open($card_style); ?>
    <?php echo view('pkde-kit::partials.blocks.accordion-layout', [
        'heading' => $heading,
        'description' => $description,
        'content' => $content,
        'itemCount' => $item_count,
        'columnThreshold' => $column_threshold,
        'themed' => (bool) $theme,
    ])->render(); ?>
    <?php echo \App\pkde_card_style_wrapper_close($card_style); ?>
</div>
