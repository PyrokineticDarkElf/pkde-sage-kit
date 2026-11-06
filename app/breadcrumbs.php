<?php

/**
 * Breadcrumbs the KIT owns (owner via the manager, MGR-77 parity): the same separator and labels on every site, whatever its Rank Math settings.
 *
 *   - Rank Math's trail is used (and its BreadcrumbList schema stays), but its settings and labels are forced to the standard through
 *     `rank_math/frontend/breadcrumb/settings` and `/strings`: the " / " separator, the "Home" label, the current page as the last crumb, ancestors
 *     and the blog page shown, no prefix. `pkde_kit/breadcrumb_standard` (array settings, array strings) may change it.
 *   - Rank Math's own switch for the module, `Helper::is_breadcrumbs_enabled()`, is true when the theme declares support for
 *     'rank-math-breadcrumbs', so the kit declares it: the trail no longer depends on that setting in wp-admin either
 *     (`pkde_kit/force_rank_math_breadcrumbs` false leaves it to the site).
 *   - There is NO kit-built trail for a site without Rank Math's breadcrumbs (owner, 2026-11-06: not needed). On shop pages WooCommerce's own trail
 *     (" / " between the crumbs) is the fallback, as before; the drift check says when Rank Math's module is off.
 */

namespace App;

/**
 * The settings and strings Rank Math is forced to.
 *
 * @return array{settings: array<string, mixed>, strings: array<string, string>}
 */
function breadcrumb_standard(): array
{
    $t = fn(string $text) => function_exists('__') ? __($text, 'sage') : $text;
    $standard = [
        'settings' => [
            'home' => true,
            'separator' => '/',
            'remove_title' => false,
            'hide_tax_name' => false,
            'show_ancestors' => true,
            'show_blog' => true,
        ],
        'strings' => [
            'prefix' => '',
            'home' => $t('Home'),
            'error404' => $t('404 Error: page not found'),
            'search_format' => $t('Results for %s'),
            'archive_format' => $t('Archives for %s'),
        ],
    ];

    return function_exists('apply_filters') ? (array) apply_filters('pkde_kit/breadcrumb_standard', $standard) : $standard;
}

/**
 * The trail for this request: Rank Math's (standardised), else WooCommerce's on shop pages (" / " between the crumbs), else ''.
 * `pkde_kit/breadcrumb_html` may replace it.
 */
function breadcrumb_trail_html(): string
{
    $html = function_exists('rank_math_get_breadcrumbs') ? trim((string) rank_math_get_breadcrumbs()) : '';

    if ($html === '' && function_exists('woocommerce_breadcrumb') && (is_woocommerce() || is_product_taxonomy())) {
        ob_start();
        woocommerce_breadcrumb(['wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="breadcrumbs">', 'wrap_after' => '</nav>', 'delimiter' => ' / ']);
        $html = trim(ob_get_clean());
    }

    return (string) apply_filters('pkde_kit/breadcrumb_html', $html);
}

if (! function_exists('add_filter')) {
    return;
}

add_action('after_setup_theme', function () {
    if (apply_filters('pkde_kit/force_rank_math_breadcrumbs', true)) {
        add_theme_support('rank-math-breadcrumbs');
    }
});

// Rank Math builds its trail from these (the filter's result wins over the site's own settings, see Breadcrumbs::__construct()).
add_filter('rank_math/frontend/breadcrumb/settings', fn($settings) => array_merge((array) $settings, breadcrumb_standard()['settings']));
add_filter('rank_math/frontend/breadcrumb/strings', fn($strings) => array_merge((array) $strings, breadcrumb_standard()['strings']));
