<?php

/**
 * The editor's preview of a Contact Form 7 form (WP-100): only a Shortcode or Paragraph that holds nothing but a [contact-form-7 ...] shortcode is answered.
 */
use function App\form_preview_shortcode;

$root = dirname(__DIR__, 2);

it('answers only a lone contact-form-7 shortcode, however the block wraps it', function () {
    expect(form_preview_shortcode('[contact-form-7 id="12" title="Contact"]'))->toBe('[contact-form-7 id="12" title="Contact"]')
        ->and(form_preview_shortcode("  <strong>[contact-form-7 id=\"12\"]</strong>&nbsp;\n"))->toBe('[contact-form-7 id="12"]')
        ->and(form_preview_shortcode('[contact-form-7]'))->toBe('[contact-form-7]')
        ->and(form_preview_shortcode('[gallery ids="1,2"]'))->toBe('')
        ->and(form_preview_shortcode('Hello [contact-form-7 id="12"]'))->toBe('')
        ->and(form_preview_shortcode('[contact-form-7 id="12"][gallery]'))->toBe('')
        ->and(form_preview_shortcode(''))->toBe('');
});

it('registers a route for people who can edit posts, strips scripts and the Turnstile widget, and loads no script in the canvas', function () use ($root) {
    $php = file_get_contents("$root/app/form-preview.php");
    $js = file_get_contents("$root/resources/js/form-preview.jsx");

    expect($php)->toContain("register_rest_route('pkde/v1', '/form-preview'", "current_user_can('edit_posts')", '<script', 'cf-turnstile')
        ->and($js)->toContain("name === 'core/shortcode'", "name === 'core/paragraph'", 'inert')
        ->and($js)->not->toContain('createElement(\'script\'') // no script reaches the canvas
        ->and(file_get_contents("$root/composer.json"))->toContain('app/form-preview.php')
        ->and(file_get_contents("$root/resources/js/kit-editor.js"))->toContain("import './form-preview.jsx';");
});
