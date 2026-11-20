<?php

/**
 * The FAQ block's shared pieces (WP-102): the row markup, the group-mode content, and ONE merged, de-duplicated FAQPage per page.
 */
use function App\faq_entities;
use function App\faq_group_content;
use function App\faq_item_html;
use function App\faq_page_entities;
use function App\faq_schema_json;

$root = dirname(__DIR__, 2);

it('draws one question as a details row that follows the section colours, with the old neutrals as the fallback', function () {
    $html = faq_item_html('Where is my order?', '<p>Check your email.</p>');

    expect($html)->toStartWith('<details class="wp-block-sage-accordion-item group break-inside-avoid')
        ->and($html)->toContain('var(--section-border,var(--color-neutral-200))', 'var(--section-heading,var(--color-neutral-950))', 'var(--section-text,inherit)', 'var(--section-muted,var(--color-neutral-400))')
        ->and($html)->toContain('<summary', 'Where is my order?', '<p>Check your email.</p>')
        ->and($html)->not->toContain(' open>')
        ->and(faq_item_html('Q', 'A', true))->toContain(' open>');
});

it('makes plain-text FAQPage entities and leaves out a question with no answer text', function () {
    $entities = faq_entities([['How long?', '<p>Two <strong>to three</strong> days.</p>'], ['No answer', '<p> </p>'], ['', '<p>No question</p>']]);

    expect($entities)->toHaveCount(1)
        ->and($entities[0])->toBe(['@type' => 'Question', 'name' => 'How long?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Two to three days.']]);
});

it('merges the entities of every FAQ block on a page into one list and drops a repeated question', function () {
    faq_page_entities([], true);
    faq_page_entities(faq_entities([['How long?', '<p>Two days.</p>'], ['Returns?', '<p>30 days.</p>']]));
    faq_page_entities(faq_entities([['how  long?', '<p>Another block, same question.</p>'], ['Care?', '<p>Cold wash.</p>']]));

    $names = array_column(faq_page_entities(), 'name');

    expect($names)->toBe(['How long?', 'Returns?', 'Care?'])
        ->and(faq_page_entities()[0]['acceptedAnswer']['text'])->toBe('Two days.'); // the first one wins

    $json = faq_schema_json();

    expect($json)->toStartWith('<script type="application/ld+json">')
        ->and(substr_count($json, '"@type":"FAQPage"'))->toBe(1)
        ->and(json_decode(strip_tags($json), true)['mainEntity'])->toHaveCount(3);

    faq_page_entities([], true);

    expect(faq_schema_json())->toBe(''); // nothing to print
});

it('shows each group\'s FAQs as rows, with the group name above them only when more than one group has FAQs', function () {
    $faq = fn(string $title) => (object) ['post_title' => $title, 'post_content' => ''];
    $rows = [
        ['group' => (object) ['name' => 'Delivery'], 'faqs' => [$faq('How long?'), $faq('Where is it?')]],
        ['group' => (object) ['name' => 'Empty'], 'faqs' => []],
        ['group' => (object) ['name' => 'Returns'], 'faqs' => [$faq('Can I return it?')]],
    ];
    $made = faq_group_content($rows, fn($post) => '<p>Answer to ' . $post->post_title . '</p>');

    expect($made['count'])->toBe(3)
        ->and(substr_count($made['html'], '<details'))->toBe(3)
        ->and($made['html'])->toContain('pkde-faq-group__name', '>Delivery<', '>Returns<')
        ->and($made['html'])->not->toContain('>Empty<')
        ->and($made['pairs'][2])->toBe(['Can I return it?', '<p>Answer to Can I return it?</p>']);

    $one = faq_group_content([$rows[0]], fn($post) => '<p>x</p>');

    expect($one['html'])->not->toContain('pkde-faq-group__name'); // a single group: no group heading
});

it('has the group attributes, a group-mode render that leaves the hand-typed mode working, and the shared item markup', function () use ($root) {
    $block = json_decode(file_get_contents("$root/resources/blocks/faq/block.json"), true);
    $render = file_get_contents("$root/resources/blocks/faq/render.php");

    expect($block['attributes'])->toHaveKeys(['groups', 'order', 'limit', 'heading', 'description', 'columnThreshold', 'theme', 'cardStyle'])
        ->and($block['attributes']['order']['enum'])->toBe(['manual', 'az'])
        ->and($render)->toContain('faqs_for_groups', 'faq_group_content', 'faq_schema_add', "'sage/accordion-item'") // both modes, one schema
        ->and(file_get_contents("$root/resources/blocks/accordion-item/render.php"))->toContain('faq_item_html')
        ->and(file_get_contents("$root/composer.json"))->toContain('app/faq.php')
        ->and(file_get_contents("$root/resources/blocks/faq/edit.jsx"))->toContain('Edit these FAQs', 'pkdeFaqAdmin', 'deprecated')
        ->and(file_get_contents("$root/resources/js/editor-settings.js"))->toContain("select('core/editor')");
});
