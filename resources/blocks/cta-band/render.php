<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * The global call-to-action band (tier 3). The structure is the kit's; the content is the site's, from config/pkde-sections.php
 * (['cta-band' => ['text' => ..., 'buttonText' => ..., 'buttonUrl' => ..., 'logo' => url or attachment id, 'logoAlt' => ..., 'theme' => 'brand-slate']]).
 * The page carries no copy of it. Without a declaration the block prints nothing (the editor, over REST, says why).
 */

$declared = \App\section_declaration('cta-band');
$text = trim((string) ($declared['text'] ?? ''));

if ($text === '') {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        printf(
            '<div %s style="padding:1rem;border:1px dashed currentColor;text-align:center;opacity:.7">%s</div>',
            get_block_wrapper_attributes(['class' => 'pkde-cta-band-empty']),
            esc_html__('Call-to-action band: nothing is declared for it yet. Add a "cta-band" entry to config/pkde-sections.php.', 'sage'),
        );
    }

    return;
}

$theme = sanitize_html_class((string) ($declared['theme'] ?? ''));
$logo = $declared['logo'] ?? '';
$logo_html = '';

if (is_numeric($logo) && (int) $logo > 0) {
    $logo_html = \App\block_image((int) $logo, '', (string) ($declared['logoAlt'] ?? ''), 'medium', ['loading' => 'lazy', 'class' => 'pkde-cta-band__logo']);
} elseif (is_string($logo) && $logo !== '') {
    $logo_html = \App\block_image(0, $logo, (string) ($declared['logoAlt'] ?? ''), 'medium', ['loading' => 'lazy', 'class' => 'pkde-cta-band__logo']);
}

echo view('pkde-kit::partials.blocks.cta-band', [
    'wrapper' => get_block_wrapper_attributes(['class' => trim('pkde-themed pkde-cta-band alignfull' . ($theme !== '' ? " is-style-{$theme}" : ''))]),
    'text' => $text,
    'buttonText' => (string) ($declared['buttonText'] ?? ''),
    'buttonUrl' => (string) ($declared['buttonUrl'] ?? ''),
    'logo' => $logo_html,
])->render();
