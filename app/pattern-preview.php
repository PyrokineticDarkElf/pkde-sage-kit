<?php

/**
 * A development-only preview of one block pattern in a bare page: `/?pkde_pattern_preview=<slug>` (optionally `&brand=<key>`). It answers ONLY when WP_ENV is
 * `development`; anywhere else, and for a slug or brand key that is not [a-z0-9-]+, it is a plain 404, so it can never run on staging or production.
 *
 * The slug is looked up in the kit's resources/patterns/<slug>.php, then in the pattern library folder named by the PKDE_PATTERNS_DIR environment variable
 * (that folder holds <family>/<slug>.html). With `brand`, `../themes/<key>.css` of that folder (a site theme file in the library's themes/ folder) is printed after the theme CSS, so the
 * pattern shows in that site's colours. The page is wp_head(), the theme's CSS and JS, the pattern's blocks through do_blocks(), wp_footer(): no site header or footer.
 */

namespace App;

/** True for a slug or brand key that is safe to turn into a file name. */
function pattern_preview_key_ok(string $value): bool
{
    return (bool) preg_match('/^[a-z0-9-]+$/', $value);
}

/** The preview answers only in development. */
function pattern_preview_enabled(?string $env = null): bool
{
    return ($env ?? (defined('WP_ENV') ? (string) WP_ENV : '')) === 'development';
}

/** The pattern library's patterns folder (PKDE_PATTERNS_DIR), or null when it is not set or is not a directory. */
function pattern_library_dir(?string $dir = null): ?string
{
    $dir ??= (string) (getenv('PKDE_PATTERNS_DIR') ?: '');
    $real = $dir !== '' ? realpath($dir) : false;

    return $real !== false && is_dir($real) ? $real : null;
}

/** The block markup of a pattern: the kit's own, else the library's. Null when there is none. A slug that is not a plain key finds nothing. */
function pattern_preview_markup(string $slug, ?string $library = null, ?string $kitDir = null): ?string
{
    if (! pattern_preview_key_ok($slug)) {
        return null;
    }

    $kit = ($kitDir ?? dirname(__DIR__) . '/resources/patterns') . "/{$slug}.php";

    if (is_file($kit)) {
        ob_start();
        include $kit;

        return (string) ob_get_clean();
    }

    foreach ($library !== null ? (glob($library . "/*/{$slug}.html") ?: []) : [] as $file) {
        return (string) file_get_contents($file);
    }

    return null;
}

/** The CSS text of a brand of the library (<patterns folder>/../themes/<key>.css), or '' when there is none or the key is not a plain key. */
function pattern_preview_brand_css(string $brand, ?string $library = null): string
{
    $file = $brand !== '' && pattern_preview_key_ok($brand) && $library !== null ? realpath($library . "/../themes/{$brand}.css") : false;

    return $file !== false && is_file($file) ? (string) file_get_contents($file) : '';
}

if (! function_exists('add_action')) {
    return;
}

add_action('template_redirect', function () {
    if (! isset($_GET['pkde_pattern_preview'])) {
        return;
    }

    $slug = (string) wp_unslash($_GET['pkde_pattern_preview']);
    $brand = (string) wp_unslash($_GET['brand'] ?? '');
    $library = pattern_library_dir();
    $markup = pattern_preview_enabled() && ($brand === '' || pattern_preview_key_ok($brand)) ? pattern_preview_markup($slug, $library) : null;

    if ($markup === null) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();

        return; // WordPress then prints its own 404
    }

    nocache_headers();
    header('X-Robots-Tag: noindex');
    echo view('pkde-kit::pattern-preview', ['html' => do_blocks($markup), 'brandCss' => pattern_preview_brand_css($brand, $library)])->render();
    exit;
}, 0);
