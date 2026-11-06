<?php

// The small shop controls' CSS (owner, 2026-11-06): the sort select's own chevron and the plain Sale/New pills.
$css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');

it('draws the sort select\'s chevron itself, the same in every browser, with its distance and image as variables', function () use ($css) {
    preg_match('/\.shop-archive__results \.woocommerce-ordering select \{(.*?)\n\}/s', $css, $rule);

    expect($rule[1] ?? '')->toContain('appearance: none')
        ->toContain('--pkde-shop-select-chevron')
        ->toContain('right var(--pkde-shop-select-inset, 1rem) center')
        ->toContain('background-repeat: no-repeat')
        // room for the chevron on the inline end: more than the left padding
        ->toMatch('/padding: 0\.45rem 2\.5rem 0\.45rem 0\.8rem;/');
});

it('does not underline a Sale or New pill on hover or focus', function () use ($css) {
    preg_match('/a\.pkde-badge:hover,\s*a\.pkde-badge:focus-visible \{(.*?)\}/s', $css, $rule);

    expect($rule[1] ?? '')->toContain('filter: brightness(0.96)')->not->toContain('text-decoration');
});
