<?php

/**
 * The editor's preview of a Contact Form 7 form: only a Shortcode or Paragraph that holds nothing but a [contact-form-7 ...] shortcode is answered.
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
