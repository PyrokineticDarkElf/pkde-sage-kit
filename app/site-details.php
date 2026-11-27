<?php

/**
 * Site details in the theme: the helper `site_detail()`, the formatting the components, blocks and shortcodes share, and the shortcodes. The values come from the platform's
 * `site-details` module (Settings > Site details); a site without that module keeps working: the data is built from its `client_*` options, the way the footer always read them; and so does a site whose Site details are not filled in yet (a bump never removes a phone number).
 * Pure functions where they can be (the unit tests load this file without WordPress); the Blade components in resources/views/components and the blocks sage/site-contact,
 * sage/site-socials and sage/site-hours print them.
 */

namespace App;

/** The social networks the kit draws an icon for (key => label); the platform module offers the same list. */
const SITE_NETWORKS = [
    'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'x' => 'X',
    'pinterest' => 'Pinterest', 'threads' => 'Threads', 'google-business' => 'Google Business Profile', 'other' => 'Other',
];

const SITE_DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

/**
 * The details of a site built from the old `client_*` options: one phone, one email, one address line, the five social profiles of the footer.
 *
 * @param  callable(string): string  $option  (name) => the option's value or ''
 * @return array<string, mixed>
 */
function site_details_from_options(callable $option, string $siteName = ''): array
{
    $phone = trim((string) $option('client_phone'));
    $email = trim((string) $option('client_email'));
    $address = trim((string) $option('client_address'));
    $socials = [];

    foreach (['facebook', 'instagram', 'linkedin', 'x', 'youtube'] as $network) {
        $url = trim((string) $option("client_$network"));

        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $socials[] = ['network' => $network, 'url' => $url, 'label' => ''];
        }
    }

    $weekly = [];
    foreach (array_keys(SITE_DAYS) as $day) {
        $weekly[$day] = ['open' => '', 'close' => ''];
    }

    return [
        'business' => ['trading_name' => $siteName, 'legal_name' => '', 'company_number' => '', 'vat_number' => '', 'registered_office' => ''],
        'phones' => $phone !== '' ? [['label' => '', 'number' => preg_replace('/[^0-9+]/', '', $phone), 'display' => $phone]] : [],
        'emails' => $email !== '' ? [['label' => '', 'address' => $email]] : [],
        'whatsapp' => '',
        'locations' => $address !== '' ? [['label' => '', 'street' => $address, 'town' => '', 'county' => '', 'postcode' => '', 'country' => '', 'map_url' => '']] : [],
        'hours' => ['weekly' => $weekly, 'exceptions' => []],
        'socials' => $socials,
    ];
}

/** `tel:` link for a number: its digits and a leading plus. */
function site_tel_href(string $number): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $number);
}

/**
 * The lines of an address, empty ones left out (the country too when it is the United Kingdom's own code).
 *
 * @param  array<string, string>  $location
 * @return list<string>
 */
function site_address_lines(array $location): array
{
    $country = trim((string) ($location['country'] ?? ''));
    $parts = [$location['street'] ?? '', $location['town'] ?? '', $location['county'] ?? '', $location['postcode'] ?? '', in_array(strtoupper($country), ['GB', 'UK'], true) ? '' : $country];

    return array_values(array_filter(array_map(fn($p) => trim((string) $p), $parts), fn($p) => $p !== ''));
}

/**
 * The weekly hours in groups of consecutive days with the same hours: [['days' => 'Monday to Friday', 'time' => '09:00 to 17:00'], ['days' => 'Sunday', 'time' => 'Closed']].
 *
 * @param  array<string, mixed>  $hours
 * @return list<array{days: string, time: string}>
 */
function site_hours_summary(array $hours): array
{
    $rows = [];

    foreach (SITE_DAYS as $key => $name) {
        $day = $hours['weekly'][$key] ?? [];
        $time = ($day['open'] ?? '') !== '' && ($day['close'] ?? '') !== '' ? $day['open'] . ' to ' . $day['close'] : 'Closed';
        $last = count($rows) - 1;

        if ($last >= 0 && $rows[$last]['time'] === $time) {
            $rows[$last]['last'] = $name;
        } else {
            $rows[] = ['first' => $name, 'last' => $name, 'time' => $time];
        }
    }

    return array_map(fn($r) => ['days' => $r['first'] === $r['last'] ? $r['first'] : $r['first'] . ' to ' . $r['last'], 'time' => $r['time']], $rows);
}

/** True when any day of the week has hours. */
function site_has_hours(array $hours): bool
{
    foreach ((array) ($hours['weekly'] ?? []) as $day) {
        if (($day['open'] ?? '') !== '') {
            return true;
        }
    }

    return false;
}

/**
 * The exceptions still to come (today and later), soonest first: [['date' => '2026-12-25', 'label' => 'Christmas Day', 'time' => 'Closed']].
 *
 * @return list<array{date: string, label: string, time: string}>
 */
function site_hours_exceptions(array $hours, string $today): array
{
    $rows = [];

    foreach ((array) ($hours['exceptions'] ?? []) as $x) {
        if (($x['date'] ?? '') >= $today) {
            $rows[] = ['date' => $x['date'], 'label' => (string) ($x['label'] ?? ''), 'time' => ($x['open'] ?? '') !== '' && ($x['close'] ?? '') !== '' ? $x['open'] . ' to ' . $x['close'] : 'Closed'];
        }
    }

    usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

    return $rows;
}

/**
 * The social profiles to print: only those with a known network and a web address, in the order stored. "Other" is named by its label.
 *
 * @param  list<array{network?: string, url?: string, label?: string}>  $socials
 * @return list<array{network: string, label: string, url: string}>
 */
function site_social_list(array $socials): array
{
    $out = [];

    foreach ($socials as $social) {
        $network = (string) ($social['network'] ?? '');
        $url = trim((string) ($social['url'] ?? ''));

        if (! isset(SITE_NETWORKS[$network]) || $url === '' || ! preg_match('~^https?://~i', $url)) {
            continue;
        }

        $label = $network === 'other' && trim((string) ($social['label'] ?? '')) !== '' ? trim((string) $social['label']) : SITE_NETWORKS[$network];
        $out[] = ['network' => $network, 'label' => $label, 'url' => $url];
    }

    return $out;
}

/** The details a site had before Site details existed (the old `client_*` options): the groups a bump must never empty. */
const SITE_LEGACY_GROUPS = ['phones', 'emails', 'locations', 'socials'];

/**
 * The details with the old `client_*` values filled in wherever Site details has nothing for a group: a bump to a kit that reads Site details must never remove a phone
 * number, an email, an address or a profile that the site's old options still hold, even before `site-details-migrate.php apply` has run. A group Site details does hold is
 * never touched.
 *
 * @param  array<string, mixed>  $details  Site details (the platform module's merged values)
 * @param  array<string, mixed>  $legacy  site_details_from_options()
 * @return array<string, mixed>
 */
function site_details_with_legacy(array $details, array $legacy): array
{
    foreach (SITE_LEGACY_GROUPS as $group) {
        if (empty($details[$group]) && ! empty($legacy[$group])) {
            $details[$group] = $legacy[$group];
        }
    }

    return $details;
}

/**
 * The groups that are being read from the old `client_*` options because Site details has nothing for them: [] when the site is migrated (or has no old values). The drift check
 * warns with this ("Site details not migrated").
 *
 * @return list<string>
 */
function site_details_legacy_fallbacks(?array $details = null, ?array $legacy = null): array
{
    $details ??= function_exists('App\\MuPlugins\\site_details') ? \App\MuPlugins\site_details() : [];
    $legacy ??= site_details_legacy();

    return array_values(array_filter(SITE_LEGACY_GROUPS, fn(string $group) => empty($details[$group]) && ! empty($legacy[$group])));
}

/** The details the old `client_*` options hold. */
function site_details_legacy(): array
{
    return site_details_from_options(fn(string $name) => function_exists('get_option') ? (string) get_option($name, '') : '', function_exists('get_bloginfo') ? (string) get_bloginfo('name') : '');
}

/**
 * All the details the site shows: the platform module's merged values, with the old `client_*` options filling any group Site details does not hold yet; or the old
 * options alone when the module is not loaded.
 */
function site_details_data(): array
{
    if (function_exists('App\\MuPlugins\\site_details')) {
        return site_details_with_legacy(\App\MuPlugins\site_details(), site_details_legacy());
    }

    return site_details_legacy();
}

/** One detail by path (`business.trading_name`, `phones.0.display`); the default when it is not there or empty. */
function site_detail(string $path, mixed $default = ''): mixed
{
    $value = site_details_data();

    foreach (explode('.', $path) as $step) {
        if (! is_array($value) || ! array_key_exists($step, $value)) {
            return $default;
        }

        $value = $value[$step];
    }

    return $value === '' || $value === [] || $value === null ? $default : $value;
}

/** @return list<array{label: string, number: string, display: string}> */
function site_phones(): array
{
    return array_values((array) site_detail('phones', []));
}

/** @return list<array{label: string, address: string}> */
function site_emails(): array
{
    return array_values((array) site_detail('emails', []));
}

/** @return list<array<string, string>> */
function site_locations(): array
{
    return array_values((array) site_detail('locations', []));
}

/**
 * Whether the header shows the phone number and the social icons (the optional slot). OFF by default (the footer always shows them); a site switches it on with
 * `add_filter('pkde_kit/header_site_details', '__return_true')`.
 */
function site_header_details(): bool
{
    return function_exists('apply_filters') && (bool) apply_filters('pkde_kit/header_site_details', false);
}

if (! function_exists('add_action')) {
    return; // outside WordPress (the unit tests load this file for the functions above)
}

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
// shortcodes, for text inside a paragraph

/** The shortcode attribute `index` (which phone, email or address; the first by default), as a number. */
function site_shortcode_index(mixed $atts): int
{
    return max(0, (int) (((array) $atts)['index'] ?? 0));
}

add_shortcode('site_phone', function ($atts) {
    $phone = site_phones()[site_shortcode_index($atts)] ?? null;

    return $phone ? sprintf('<a href="%s">%s</a>', esc_url(site_tel_href($phone['number'])), esc_html($phone['display'] ?: $phone['number'])) : '';
});

add_shortcode('site_email', function ($atts) {
    $email = site_emails()[site_shortcode_index($atts)]['address'] ?? '';

    // antispambot() turns the address into character references: a person sees it, a simple scraper does not
    return $email !== '' ? sprintf('<a href="mailto:%1$s">%1$s</a>', antispambot($email)) : '';
});

add_shortcode('site_address', function ($atts) {
    $location = site_locations()[site_shortcode_index($atts)] ?? null;

    return $location ? esc_html(implode(', ', site_address_lines($location))) : '';
});

add_shortcode('site_hours', function () {
    $summary = site_hours_summary((array) site_detail('hours', []));

    return site_has_hours((array) site_detail('hours', [])) ? esc_html(implode('; ', array_map(fn($r) => $r['days'] . ' ' . $r['time'], $summary))) : '';
});
