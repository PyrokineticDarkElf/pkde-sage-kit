<?php

/**
 * Contact Form 7 forms are found by a stable key, not by title or ID (TPL-186). WordPress is stood in for by two global functions.
 */
use function App\form_id;
use function App\form_shortcode;

if (! function_exists('get_posts')) {
    function get_posts(array $args): array
    {
        $GLOBALS['formKeyQueries'][] = $args;

        return ($args['meta_key'] ?? '') === '_pkde_form_key' ? (array) ($GLOBALS['formKeyForms'][$args['meta_value']] ?? []) : [];
    }
}

if (! function_exists('shortcode_exists')) {
    function shortcode_exists(string $tag): bool
    {
        return $tag === 'contact-form-7' && ($GLOBALS['formKeyPlugin'] ?? true);
    }
}

it('finds the form by the meta key, cached for the request, 0 when no form has it', function () {
    $GLOBALS['formKeyForms'] = ['contact' => [12], 'park-home-enquiry' => [30]];
    $GLOBALS['formKeyQueries'] = [];

    expect(form_id())->toBe(12)
        ->and(form_id('contact'))->toBe(12)
        ->and(form_id('park-home-enquiry'))->toBe(30)
        ->and(form_id('quote'))->toBe(0)
        ->and(count($GLOBALS['formKeyQueries']))->toBe(3); // 'contact' asked twice, queried once

    $query = $GLOBALS['formKeyQueries'][0];
    expect($query['post_type'])->toBe('wpcf7_contact_form')->and($query['meta_key'])->toBe('_pkde_form_key')->and($query['post_status'])->toBe('any');
});

it('prints the shortcode only for a form that exists and a plugin that is on', function () {
    $GLOBALS['formKeyForms'] = ['contact' => [12]];
    expect(form_shortcode('contact'))->toBe('[contact-form-7 id="12"]')
        ->and(form_shortcode('missing-key'))->toBe('');
});
