<?php

namespace App;

/**
 * The FAQ block's shared pieces (WP-102): the markup of one question, the group-mode content, and ONE FAQPage JSON-LD per page that every FAQ block adds to.
 *
 * Group mode reads the FAQs from the platform's `faq` module (`App\MuPlugins\faqs_for_groups()`: the post type pkde_faq, grouped by pkde_faq_group, in each group's own
 * order). The older hand-typed mode (Accordion Item blocks inside the FAQ block) still renders, through the same markup and the same schema; it is deprecated.
 */

/** One question as a <details> row: the same markup as the Accordion Item block, following the section's colours (--section-*), with the old neutral colours as the fallback. */
function faq_item_html(string $question, string $answerHtml, bool $open = false, string $wrapperAttributes = ''): string
{
    $wrapperAttributes = $wrapperAttributes !== '' ? $wrapperAttributes : 'class="wp-block-sage-accordion-item group break-inside-avoid border-b-(length:--pkde-border-thin) border-[color:var(--section-border,var(--color-neutral-200))] py-4"';
    $question = function_exists('wp_kses_post') ? wp_kses_post($question) : htmlspecialchars($question, ENT_QUOTES);

    return '<details ' . $wrapperAttributes . ($open ? ' open' : '') . '>'
        . '<summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-semibold text-[color:var(--section-heading,var(--color-neutral-950))] [&::-webkit-details-marker]:hidden">'
        . $question
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 flex-none text-[color:var(--section-muted,var(--color-neutral-400))] transition-transform duration-[var(--pkde-motion-base)] ease-[var(--pkde-ease-out)] motion-reduce:transition-none group-open:rotate-45">'
        . '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" /></svg></summary>'
        . '<div class="prose-content mt-3 pr-8 text-[color:var(--section-text,inherit)]">' . $answerHtml . '</div></details>';
}

/**
 * The plain-text FAQPage entities of a list of [question, answer html] pairs; a pair with no question or no answer text is left out.
 *
 * @param  list<array{0: string, 1: string}>  $pairs
 * @return list<array<string, mixed>>
 */
function faq_entities(array $pairs): array
{
    $strip = fn(string $html) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)) ?? '');
    $entities = [];

    foreach ($pairs as [$question, $answer]) {
        $name = $strip($question);
        $text = $strip($answer);

        if ($name !== '' && $text !== '') {
            $entities[] = ['@type' => 'Question', 'name' => $name, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $text]];
        }
    }

    return $entities;
}

/**
 * The page's FAQ entities so far, merged across every FAQ block and de-duplicated by question (case and spacing ignored). Pass entities to add them; call with none to read.
 *
 * @param  list<array<string, mixed>>  $add
 * @return list<array<string, mixed>>
 */
function faq_page_entities(array $add = [], bool $reset = false): array
{
    // WP-105 phase 1: pkde/platform's `faq` module owns the page's FAQ schema when it is on (PKDE_PLATFORM_FAQ_SCHEMA); this is the fallback.
    if (defined('PKDE_PLATFORM_FAQ_SCHEMA')) {
        return \App\MuPlugins\faq_page_entities($add, $reset);
    }

    static $all = [];

    if ($reset) {
        $all = [];
    }

    foreach ($add as $entity) {
        $key = strtolower(preg_replace('/\s+/', ' ', (string) ($entity['name'] ?? '')) ?? '');

        if ($key !== '' && ! isset($all[$key])) {
            $all[$key] = $entity;
        }
    }

    return array_values($all);
}

/** The one FAQPage JSON-LD of the page, or '' when there is nothing to print or the site turned it off (filter `pkde_kit/faq_schema`, or Rank Math's own FAQ block is on the page). */
function faq_schema_json(): string
{
    if (defined('PKDE_PLATFORM_FAQ_SCHEMA')) {
        return \App\MuPlugins\faq_schema_json();
    }

    $entities = faq_page_entities();

    if (! $entities) {
        return '';
    }

    if (function_exists('apply_filters') && apply_filters('pkde_kit/faq_schema', true) === false) {
        return '';
    }

    if (function_exists('is_singular') && is_singular() && function_exists('has_block') && has_block('rank-math/faq-block')) {
        return ''; // Rank Math prints its own FAQPage for its block; never twice
    }

    return '<script type="application/ld+json">' . (function_exists('wp_json_encode') ? wp_json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities]) : json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities])) . '</script>' . "\n";
}

/** Add a block's entities to the page's single FAQPage (printed in the footer, once). */
function faq_schema_add(array $entities): void
{
    if (defined('PKDE_PLATFORM_FAQ_SCHEMA')) {
        \App\MuPlugins\faq_schema_add($entities);

        return;
    }

    static $hooked = false;

    faq_page_entities($entities);

    if (! $hooked && function_exists('add_action')) {
        $hooked = true;
        add_action('wp_footer', function () {
            echo faq_schema_json();
        });
    }
}

/**
 * The group-mode content of a FAQ block: each group's FAQs as rows, with the group's name above them when the block shows more than one group.
 *
 * @param  list<array{group: object, faqs: list<object>}>  $groups  from App\MuPlugins\faqs_for_groups()
 * @param  callable(object): string  $answer  the rendered answer of a FAQ post
 * @return array{html: string, count: int, pairs: list<array{0: string, 1: string}>}
 */
function faq_group_content(array $groups, callable $answer): array
{
    $several = count(array_filter($groups, fn($row) => $row['faqs'])) > 1;
    $html = '';
    $count = 0;
    $pairs = [];

    foreach ($groups as $row) {
        if (! $row['faqs']) {
            continue;
        }

        $html .= '<div class="pkde-faq-group">';

        if ($several) {
            $html .= '<h3 class="pkde-faq-group__name mt-6 font-display text-base font-semibold uppercase tracking-wide text-[color:var(--section-muted,var(--color-neutral-600))]">'
                . (function_exists('esc_html') ? esc_html($row['group']->name) : htmlspecialchars($row['group']->name)) . '</h3>';
        }

        foreach ($row['faqs'] as $faq) {
            $answerHtml = $answer($faq);
            $html .= faq_item_html((string) $faq->post_title, $answerHtml);
            $pairs[] = [(string) $faq->post_title, $answerHtml];
            $count++;
        }

        $html .= '</div>';
    }

    return ['html' => $html, 'count' => $count, 'pairs' => $pairs];
}

if (! function_exists('add_filter')) {
    return;
}

// The FAQ block's group picker in the editor: the groups (anyone who can edit the page may choose them, whether or not they may edit the FAQs), and the FAQs screen's address for
// the people who may manage them.
add_filter('block_editor_settings_all', function ($settings) {
    if (! function_exists('taxonomy_exists') || ! taxonomy_exists('pkde_faq_group')) {
        return $settings;
    }

    $terms = get_terms(['taxonomy' => 'pkde_faq_group', 'hide_empty' => false, 'orderby' => 'name']);
    $settings['pkdeFaqGroups'] = is_wp_error($terms) ? [] : array_map(fn($term) => ['id' => (int) $term->term_id, 'name' => $term->name, 'count' => (int) $term->count], $terms);
    $settings['pkdeFaqAdmin'] = current_user_can('manage_pkde_faqs') ? admin_url('edit.php?post_type=pkde_faq') : '';

    return $settings;
});
