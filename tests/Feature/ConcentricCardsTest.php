<?php

// Concentric card radii (WP-108): the media of a padded card is the card's radius minus its padding, a flush picture takes the card's radius on the corners it shares.
$root = dirname(__DIR__, 2);

it('defines the card radius and padding tokens and the inset and flush helpers', function () use ($root) {
    $css = file_get_contents("$root/resources/css/kit.css");

    expect($css)->toContain('--pkde-card-radius: var(--radius-2xl);', '--pkde-card-padding: 1rem;')
        ->and($css)->toMatch('/\.pkde-card__media--inset \{\s*border-radius: max\(0px, calc\(var\(--pkde-card-radius\) - var\(--pkde-card-padding\)\)\);/')
        ->and($css)->toMatch('/\.pkde-card--flush \{\s*overflow: clip;/')
        ->and($css)->toMatch('/\.pkde-card__media--flush \{\s*border-radius: var\(--pkde-card-radius\) var\(--pkde-card-radius\) 0 0;/');
});

it('draws every padded kit card from the tokens, with its picture concentric', function () use ($root) {
    $themes = file_get_contents("$root/resources/css/section-themes.css");
    $sections = file_get_contents("$root/resources/css/sections.css");

    expect($themes)->toContain('border-radius: var(--pkde-card-radius);', 'padding: var(--pkde-card-padding);')
        ->and($themes)->toMatch('/\.pkde-product-card__media\)[^{]*\{\s*border-radius: max\(0px, calc\(var\(--pkde-card-radius\) - var\(--pkde-card-padding\)\)\);/s')
        ->and($sections)->toContain('--pkde-card-padding: 1.5rem;', 'var(--pkde-feature-radius, var(--pkde-card-radius))', 'var(--pkde-quote-radius, var(--pkde-card-radius))')
        ->and($sections)->toMatch('/\.pkde-features--cards \.pkde-feature__media img \{\s*border-radius: max\(0px, calc\(/')
        ->and(file_get_contents("$root/resources/views/partials/content.blade.php"))->toContain('pkde-card__media');
});
