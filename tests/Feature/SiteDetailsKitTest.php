<?php

// app/site-details.php: the formatting and the legacy fallback are plain functions; the components, blocks and the screen are proved in a browser on the template stack.
use function App\contact_lines;
use function App\site_address_lines;
use function App\site_details_from_options;
use function App\site_has_hours;
use function App\site_hours_exceptions;
use function App\site_hours_summary;
use function App\site_social_list;
use function App\site_tel_href;

$root = dirname(__DIR__, 2);

it('builds the details from the old client_* options exactly as the footer read them', function () {
    $options = ['client_phone' => '+44 (0) 1234 567890', 'client_email' => 'hello@example.test', 'client_address' => '1 High St, Leeds', 'client_facebook' => 'https://facebook.com/x', 'client_instagram' => 'nope', 'client_youtube' => 'https://youtube.com/@x'];
    $d = site_details_from_options(fn(string $name) => $options[$name] ?? '', 'Acme');

    expect($d['business']['trading_name'])->toBe('Acme')
        ->and($d['phones'])->toBe([['label' => '', 'number' => '+4401234567890', 'display' => '+44 (0) 1234 567890']])
        ->and($d['emails'])->toBe([['label' => '', 'address' => 'hello@example.test']])
        ->and($d['locations'][0]['street'])->toBe('1 High St, Leeds')
        ->and(array_column($d['socials'], 'network'))->toBe(['facebook', 'youtube']); // fixed order, a bad URL left out

    // the footer's three lines, as before: value as typed, tel: with the digits and the plus
    $GLOBALS['pkde_test_options'] = $options;
    $lines = contact_lines();
    expect($lines['phone'])->toBe(['value' => '+44 (0) 1234 567890', 'href' => 'tel:+4401234567890'])
        ->and($lines['email'])->toBe(['value' => 'hello@example.test', 'href' => 'mailto:hello@example.test'])
        ->and($lines['address'])->toBe(['value' => '1 High St, Leeds', 'href' => null])
        ->and(array_keys($lines))->toBe(['email', 'phone', 'address']);

    $GLOBALS['pkde_test_options'] = [];
    expect(contact_lines())->toBe([]);
});

it('formats a tel link, an address and the social list', function () {
    expect(site_tel_href('+44 (0) 1234 567-890'))->toBe('tel:+4401234567890')
        ->and(site_address_lines(['street' => '1 High St', 'town' => 'Leeds', 'county' => '', 'postcode' => 'LS1 1AA', 'country' => 'GB']))->toBe(['1 High St', 'Leeds', 'LS1 1AA'])
        ->and(site_address_lines(['street' => '5 Rue X', 'town' => 'Paris', 'country' => 'France']))->toBe(['5 Rue X', 'Paris', 'France']);

    expect(site_social_list([
        ['network' => 'instagram', 'url' => 'https://instagram.com/a'], ['network' => 'other', 'url' => 'https://blog.test', 'label' => 'Our blog'],
        ['network' => 'bogus', 'url' => 'https://x.test'], ['network' => 'x', 'url' => 'javascript:alert(1)'], ['network' => 'tiktok', 'url' => ''],
    ]))->toBe([['network' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/a'], ['network' => 'other', 'label' => 'Our blog', 'url' => 'https://blog.test']]);
});

it('groups consecutive days with the same hours and lists only the exceptions still to come', function () {
    $weekly = ['mon' => ['open' => '09:00', 'close' => '17:00'], 'tue' => ['open' => '09:00', 'close' => '17:00'], 'wed' => ['open' => '09:00', 'close' => '17:00'], 'thu' => ['open' => '09:00', 'close' => '17:00'],
        'fri' => ['open' => '09:00', 'close' => '17:00'], 'sat' => ['open' => '10:00', 'close' => '14:00'], 'sun' => ['open' => '', 'close' => '']];

    expect(site_hours_summary(['weekly' => $weekly]))->toBe([['days' => 'Monday to Friday', 'time' => '09:00 to 17:00'], ['days' => 'Saturday', 'time' => '10:00 to 14:00'], ['days' => 'Sunday', 'time' => 'Closed']])
        ->and(site_has_hours(['weekly' => $weekly]))->toBeTrue()
        ->and(site_has_hours(['weekly' => ['mon' => ['open' => '', 'close' => '']]]))->toBeFalse();

    // a split week: Monday and Wednesday the same, Tuesday different, are three groups
    expect(array_column(site_hours_summary(['weekly' => ['mon' => ['open' => '09:00', 'close' => '12:00'], 'tue' => ['open' => '', 'close' => ''], 'wed' => ['open' => '09:00', 'close' => '12:00']]]), 'days'))->toContain('Monday', 'Tuesday', 'Wednesday');

    $exceptions = [['date' => '2026-12-26', 'label' => 'Boxing Day', 'open' => '', 'close' => ''], ['date' => '2026-10-01', 'label' => 'Past', 'open' => '', 'close' => ''], ['date' => '2026-12-24', 'label' => 'Christmas Eve', 'open' => '09:00', 'close' => '13:00']];
    expect(site_hours_exceptions(['exceptions' => $exceptions], '2026-11-23'))->toBe([
        ['date' => '2026-12-24', 'label' => 'Christmas Eve', 'time' => '09:00 to 13:00'], ['date' => '2026-12-26', 'label' => 'Boxing Day', 'time' => 'Closed'],
    ]);
});

it('ships the components, the three blocks, the styles and the shortcodes, loaded from the package', function () use ($root) {
    foreach (['site-phone', 'site-email', 'site-address', 'site-hours', 'social-links'] as $component) {
        expect(is_file("$root/resources/views/components/$component.blade.php"))->toBeTrue($component);
    }

    foreach (['site-contact' => 'Contact details', 'site-socials' => 'Social links', 'site-hours' => 'Opening hours'] as $slug => $title) {
        $meta = json_decode(file_get_contents("$root/resources/blocks/$slug/block.json"), true);
        expect($meta['name'])->toBe("sage/$slug")->and($meta['title'])->toBe($title)
            ->and(is_file("$root/resources/blocks/$slug/render.php"))->toBeTrue()->and(file_get_contents("$root/resources/blocks/$slug/edit.jsx"))->toContain('ServerSideRender', 'save: () => null');
    }

    $source = file_get_contents("$root/app/site-details.php");
    expect($source)->toContain("add_shortcode('site_phone'", "add_shortcode('site_email'", "add_shortcode('site_address'", "add_shortcode('site_hours'", 'antispambot')
        ->and(file_get_contents("$root/src/KitServiceProvider.php"))->toContain('anonymousComponentPath')
        ->and(file_get_contents("$root/composer.json"))->toContain('app/site-details.php')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./site-details.css";')
        ->and(file_get_contents("$root/resources/views/components/site-email.blade.php"))->toContain('antispambot');
});

it('has an optional header slot, off unless a site turns it on', function () use ($root) {
    $source = file_get_contents("$root/app/site-details.php");

    expect($source)->toContain("apply_filters('pkde_kit/header_site_details', false)")
        ->and(is_file("$root/resources/views/partials/header-site-details.blade.php"))->toBeTrue()
        ->and(file_get_contents("$root/resources/views/partials/header-site-details.blade.php"))->toContain('tel:', 'social-links');
});

it('fills a group Site details does not hold from the old client_* options, and never touches one it holds', function () {
    $legacy = \App\site_details_from_options(fn(string $name) => ['client_phone' => '07700 900123', 'client_email' => 'hello@example.test', 'client_address' => '1 High St', 'client_instagram' => 'https://instagram.test/example'][$name] ?? '', 'Electrical Labs');

    // nothing in Site details yet: the phone, email, address and profile of the old options show
    $merged = \App\site_details_with_legacy(['phones' => [], 'emails' => [], 'locations' => [], 'socials' => [], 'business' => ['trading_name' => '']], $legacy);
    expect($merged['phones'][0]['display'])->toBe('07700 900123')
        ->and($merged['emails'][0]['address'])->toBe('hello@example.test')
        ->and($merged['locations'][0]['street'])->toBe('1 High St')
        ->and($merged['socials'][0]['network'])->toBe('instagram')
        ->and($merged['business']['trading_name'])->toBe('') // the name is not a fallback: it never was a stored value
        ->and(\App\site_details_legacy_fallbacks(['phones' => [], 'emails' => [], 'locations' => [], 'socials' => []], $legacy))->toBe(['phones', 'emails', 'locations', 'socials']);

    // a group Site details holds stays as it is, the others fall back
    $held = ['phones' => [['label' => '', 'number' => '+441132000000', 'display' => '0113 200 0000']], 'emails' => [], 'locations' => [], 'socials' => []];
    $merged = \App\site_details_with_legacy($held, $legacy);
    expect($merged['phones'])->toBe($held['phones'])->and($merged['emails'][0]['address'])->toBe('hello@example.test')
        ->and(\App\site_details_legacy_fallbacks($held, $legacy))->toBe(['emails', 'locations', 'socials']);

    // nothing old and nothing new: nothing appears, and nothing is reported
    expect(\App\site_details_with_legacy(['phones' => []], \App\site_details_from_options(fn() => '', '')))->toBe(['phones' => []])
        ->and(\App\site_details_legacy_fallbacks(['phones' => []], \App\site_details_from_options(fn() => '', '')))->toBe([]);
});
