<?php

/**
 * A page that starts or ends with a full-width block has no padding there (TPL-278); prose keeps it.
 */
use function App\page_content_padding;

$b = static fn (string $name, array $attrs = []) => ['blockName' => $name, 'attrs' => $attrs];
$blank = ['blockName' => null, 'attrs' => []];

it('pads a page of prose on both sides', function () use ($b) {
    expect(page_content_padding([$b('core/paragraph'), $b('core/heading')]))->toBe('pt-16 pb-16');
    expect(page_content_padding([]))->toBe('pt-16 pb-16');
});

it('drops the top padding when the first block is full width, and the bottom when the last is', function () use ($b, $blank) {
    expect(page_content_padding([$blank, $b('sage/section'), $b('core/paragraph')]))->toBe('pt-0 pb-16');
    expect(page_content_padding([$b('core/paragraph'), $b('core/group', ['align' => 'full']), $blank]))->toBe('pt-16 pb-0');
    expect(page_content_padding([$b('sage/section'), $b('sage/section'), $b('sage/cta-band')]))->toBe('pt-0 pb-0');
});

it('keeps the padding for a wide or unaligned group', function () use ($b) {
    expect(page_content_padding([$b('core/group', ['align' => 'wide']), $b('core/group')]))->toBe('pt-16 pb-16');
});
