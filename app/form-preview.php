<?php

namespace App;

/**
 * The block editor's preview of a Contact Form 7 form: a Shortcode or Paragraph block that holds only `[contact-form-7 ...]` shows the real form in the canvas
 * (resources/js/form-preview.jsx asks this route), not the raw shortcode. Display only: scripts and the Turnstile widget are removed from the markup, no CF7 script is loaded
 * in the canvas, and the preview is inert, so nothing is submitted and no Turnstile request goes out. Only `contact-form-7` shortcodes are answered; any other text gets an
 * empty answer. Anyone who can edit posts may ask (an Editor sees the forms an Administrator inserted).
 */
function form_preview_shortcode(string $text): string
{
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\u{00A0}");

    return preg_match('/^\[contact-form-7(?:\s[^\[\]]*)?\]$/', $text) === 1 ? $text : '';
}

/** @return array{html: string, styles: string[]} */
function form_preview(string $text): array
{
    $shortcode = form_preview_shortcode($text);

    if ($shortcode === '' || ! shortcode_exists('contact-form-7')) {
        return ['html' => '', 'styles' => []];
    }

    $html = do_shortcode($shortcode);
    $html = preg_replace('~<script\b.*?</script>~is', '', $html) ?? '';
    $html = preg_replace('~(<form\b[^>]*?)\saction="[^"]*"~i', '$1 action="#"', $html) ?? '';
    $html = preg_replace('~<div\b[^>]*\bcf-turnstile\b[^>]*>\s*</div>~is', '', $html) ?? '';
    $styles = function_exists('wpcf7_plugin_url') ? [add_query_arg('ver', defined('WPCF7_VERSION') ? WPCF7_VERSION : '1', wpcf7_plugin_url('includes/css/styles.css'))] : [];

    return ['html' => $html, 'styles' => $styles];
}

if (! function_exists('add_action')) {
    return; // outside WordPress (the unit tests)
}

// pkde/platform's `cf7-editor-preview` module registers this route when it is on (PKDE_PLATFORM_CF7_EDITOR_PREVIEW); this copy is the fallback.
if (! defined('PKDE_PLATFORM_CF7_EDITOR_PREVIEW')) {
    add_action('rest_api_init', function () {
        register_rest_route('pkde/v1', '/form-preview', [
            'methods' => 'GET',
            'permission_callback' => fn() => current_user_can('edit_posts'),
            'args' => ['shortcode' => ['type' => 'string', 'required' => true]],
            'callback' => fn(\WP_REST_Request $request) => rest_ensure_response(form_preview((string) $request->get_param('shortcode'))),
        ]);
    });
}
