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
 * The deepest of a product's categories (the most ancestors; the first among equals), as WooCommerce's own trail picks it. Pure: $depth maps a term to
 * its number of ancestors.
 *
 * @param  list<object>  $terms
 */
function deepest_term(array $terms, callable $depth): ?object
{
    $best = null;
    $bestDepth = -1;

    foreach ($terms as $term) {
        $d = (int) $depth($term);

        if ($d > $bestDepth) {
            $best = $term;
            $bestDepth = $d;
        }
    }

    return $best;
}

/**
 * Puts a product's category path into its trail: Rank Math only adds one when its "primary taxonomy" setting for products is set, and that is a
 * per-site wp-admin setting the kit does not control, so a single product's trail was "Home / Product" (tcs, TCS-112; before it was WooCommerce's
 * "Home / Wall Clocks / Modern Wall Clocks / Product"). The crumbs [label, url] of the path go in front of the last crumb (the product), unless the trail
 * already has them. Pure.
 *
 * @param  list<array{0: string, 1: string}>  $crumbs  the trail, the current page last
 * @param  list<array{0: string, 1: string}>  $path  the category and its ancestors, outermost first
 * @return list<array{0: string, 1: string}>
 */
function crumbs_with_category_path(array $crumbs, array $path): array
{
    if (! $crumbs || ! $path) {
        return $crumbs;
    }

    $urls = array_column($crumbs, 1);
    $path = array_values(array_filter($path, fn($crumb) => ! in_array($crumb[1], $urls, true)));

    if (! $path) {
        return $crumbs;
    }

    $last = array_pop($crumbs);

    return [...$crumbs, ...$path, $last];
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

// A single product's trail gets its category path (the deepest category and its ancestors), whatever the site's Rank Math product settings say. Rank
// Math's own crumbs list feeds its BreadcrumbList schema too, so the schema carries the same path.
add_filter('rank_math/frontend/breadcrumb/items', function ($crumbs) {
    if (! function_exists('is_product') || ! is_product() || ! is_array($crumbs)) {
        return $crumbs;
    }

    $terms = get_the_terms(get_the_ID(), 'product_cat');

    if (! is_array($terms) || ! $terms) {
        return $crumbs;
    }

    $term = deepest_term($terms, fn($t) => count(get_ancestors($t->term_id, 'product_cat', 'taxonomy')));
    $path = [];

    foreach (array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy')) as $id) {
        $ancestor = get_term($id, 'product_cat');
        if ($ancestor instanceof \WP_Term) {
            $path[] = [$ancestor->name, (string) get_term_link($ancestor)];
        }
    }
    $path[] = [$term->name, (string) get_term_link($term)];

    return crumbs_with_category_path($crumbs, $path);
}, 10, 1);
