<?php

/**
 * The "Eyebrow" block style of core Paragraph and Heading (TPL-281): it also carries .pkde-eyebrow on the page, so the same look and colour as the kit's own eyebrows apply.
 */
use function App\eyebrow_blocks;
use function App\eyebrow_class;

it('is offered for core Paragraph and Heading only', function () {
    expect(eyebrow_blocks())->toBe(['core/paragraph', 'core/heading']);
});

it('leaves any other block, and a block with another style, untouched', function () {
    $html = '<p class="is-style-eyebrow">Hello</p>';
    expect(eyebrow_class($html, ['blockName' => 'core/quote', 'attrs' => ['className' => 'is-style-eyebrow']]))->toBe($html)
        ->and(eyebrow_class('<p>Hello</p>', ['blockName' => 'core/paragraph', 'attrs' => []]))->toBe('<p>Hello</p>')
        ->and(eyebrow_class('<p>Hello</p>', ['blockName' => 'core/paragraph', 'attrs' => ['className' => 'is-style-outline']]))->toBe('<p>Hello</p>');
});

it('adds pkde-eyebrow to the first tag of an Eyebrow styled block', function () {
    if (! class_exists('WP_HTML_Tag_Processor')) {
        $this->markTestSkipped('WordPress is not loaded: the tag processor is core\'s.');
    }

    expect(eyebrow_class('<h2 class="wp-block-heading is-style-eyebrow">F.A.Q.</h2>', ['blockName' => 'core/heading', 'attrs' => ['className' => 'is-style-eyebrow']]))
        ->toContain('pkde-eyebrow');
});
