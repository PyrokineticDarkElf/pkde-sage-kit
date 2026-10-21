<?php

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
 */

$heading = $attributes['heading'] ?? '';
$description = $attributes['description'] ?? '';
$column_threshold = max(1, (int) ($attributes['columnThreshold'] ?? 6));
$theme = sanitize_html_class($attributes['theme'] ?? '');
$card_style = \App\pkde_sanitize_card_style($attributes['cardStyle'] ?? 'white');

// Built from the parsed accordion-item children directly (each child's own
// "heading" attribute is the question) rather than scraping the rendered
// $content HTML — re-rendering each child in isolation to get its answer
// text is simpler and more reliable than parsing markup back out of HTML.
// Rendering just the item's OWN inner blocks (not render_block() on the
// whole accordion-item) matters: the item's full render includes its
// <summary> heading too, which would otherwise duplicate the question
// inside "text" — confirmed directly by generating the schema and seeing
// the question repeated at the start of its own answer.
$faq_entities = [];
$item_count = 0;

foreach ($block->parsed_block['innerBlocks'] ?? [] as $inner_block) {
    if (($inner_block['blockName'] ?? '') !== 'sage/accordion-item') {
        continue;
    }

    $item_count++;

    $question = trim(wp_strip_all_tags($inner_block['attrs']['heading'] ?? ''));
    $answer_html = '';

    foreach ($inner_block['innerBlocks'] ?? [] as $answer_block) {
        $answer_html .= render_block($answer_block);
    }

    $answer_text = trim(wp_strip_all_tags($answer_html));

    if ($question && $answer_text) {
        $faq_entities[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer_text,
            ],
        ];
    }
}

if ($faq_entities) {
    add_action('wp_footer', function () use ($faq_entities) {
        echo '<script type="application/ld+json">' . wp_json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faq_entities,
        ]) . '</script>' . "\n";
    });
}

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
