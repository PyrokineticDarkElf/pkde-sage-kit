<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * FAQs block frontend output. Visually identical to the Accordion block
 * (same markup shape, same partials/blocks/accordion-layout.blade.php) —
 * the reason this is a separate block, per docs/planning/block-plan.md's
 * own recommendation, is the JSON-LD FAQPage structured data below, which
 * only makes sense to emit when a block is genuinely marked as the site's
 * FAQs, not just any collapsible list.
 *
 * Theming: see accordion/render.php's own comment — same conditional
 * boxed-panel pattern (Hero/CTA's, not the loop blocks' forced-full-bleed
 * one), and Accordion Item picks up the theme/card background purely via
 * CSS scoping/inheritance (app.css's
 * `.pkde-section .wp-block-sage-accordion-item` rule, and
 * App\pkde_card_style_wrapper_open()'s own comment).
 *
 * @var array $attributes
 * @var string $content
 * @var WP_Block $block
 *
 * Two modes: GROUP MODE (`groups` is set: the FAQs of those groups, from the platform's pkde_faq post type) and the deprecated HAND-TYPED MODE (Accordion Item blocks inside).
 */

$heading = $attributes['heading'] ?? '';
$description = $attributes['description'] ?? '';
$column_threshold = max(1, (int) ($attributes['columnThreshold'] ?? 6));
$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');

$group_ids = array_values(array_filter(array_map('intval', (array) ($attributes['groups'] ?? []))));
$order = ($attributes['order'] ?? 'manual') === 'az' ? 'az' : 'manual';
$limit = max(0, (int) ($attributes['limit'] ?? 0));
$pairs = []; // [question, answer html] of every row shown, for the page's one FAQPage
$item_count = 0;

if ($group_ids) {
    // GROUP MODE: the FAQs of the chosen groups, from the platform's faq module, in each group's own order. Nothing to show (module off, no published FAQ yet): nothing printed.
    $rows = function_exists('App\MuPlugins\faqs_for_groups') ? \App\MuPlugins\faqs_for_groups($group_ids, $order, $limit) : [];
    $made = \App\faq_group_content($rows, fn($faq) => do_blocks($faq->post_content));

    if (! $made['count']) {
        return;
    }

    $content = $made['html'];
    $item_count = $made['count'];
    $pairs = $made['pairs'];
} else {
    // HAND-TYPED MODE (deprecated, kept so pages keep rendering until they are migrated): built from the parsed accordion-item children directly (each child's own
    // "heading" attribute is the question) rather than scraping the rendered $content HTML. Rendering just the item's OWN inner blocks (not render_block() on the whole
    // accordion-item) matters: the item's full render includes its <summary> heading too, which would otherwise duplicate the question inside the answer.
    foreach ($block->parsed_block['innerBlocks'] ?? [] as $inner_block) {
        if (($inner_block['blockName'] ?? '') !== 'sage/accordion-item') {
            continue;
        }

        $item_count++;
        $answer_html = '';

        foreach ($inner_block['innerBlocks'] ?? [] as $answer_block) {
            $answer_html .= render_block($answer_block);
        }

        $pairs[] = [(string) ($inner_block['attrs']['heading'] ?? ''), $answer_html];
    }
}

// one FAQPage per page, merged across every FAQ block and de-duplicated (App\faq_schema_add; skipped when Rank Math's own FAQ block is on the page)
\App\faq_schema_add(\App\faq_entities($pairs));

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
