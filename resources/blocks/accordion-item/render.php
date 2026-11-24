<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Accordion Item block frontend output — a single <details>/<summary> pair.
 * Only ever rendered as a child of Accordion or FAQs — native HTML handles
 * the collapse/expand interaction, so no JS is needed for it at all.
 *
 * @var array $attributes
 * @var string $content
 */

$heading = $attributes['heading'] ?? '';
$open = ! empty($attributes['open']);

// break-inside-avoid keeps one item from being split across a CSS-columns
// boundary when the parent Accordion/FAQs block reflows into newspaper-style
// columns above its own columnThreshold — harmless no-op in the default
// single-column layout, since there's no column boundary to avoid there.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'group break-inside-avoid border-b-(length:--pkde-border-thin) border-[color:var(--section-border,var(--color-neutral-200))] py-4',
]);
?>
<?php echo \App\faq_item_html((string) $heading, (string) $content, $open, $wrapper_attributes); ?>
