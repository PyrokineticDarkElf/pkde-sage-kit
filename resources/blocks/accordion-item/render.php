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
    'class' => 'group break-inside-avoid border-b border-neutral-200 py-4',
]);
?>
<details <?php echo $wrapper_attributes; ?><?php echo $open ? ' open' : ''; ?>>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-semibold text-neutral-950 [&::-webkit-details-marker]:hidden">
        <?php echo wp_kses_post($heading); ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 flex-none text-neutral-400 transition-transform duration-200 group-open:rotate-45">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
        </svg>
    </summary>
    <div class="prose-content mt-3 pr-8">
        <?php echo $content; ?>
    </div>
</details>
