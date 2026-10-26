<?php

/**
 * Small template helpers.
 */

namespace App;

/**
 * Makes a root-relative URL (/app/uploads/...) absolute. Stored content keeps root-relative URLs so a domain
 * change needs no search-replace, but EWWW's WebP rewriter and any CDN only act on absolute URLs, so anything
 * printed into an <img> goes through this first. Absolute and protocol-relative URLs pass through unchanged.
 */
function absolute_url(?string $url): string
{
    $url = (string) $url;

    return ($url !== '' && str_starts_with($url, '/') && ! str_starts_with($url, '//')) ? home_url($url) : $url;
}

/**
 * The client's contact details that are set (an unset option is simply absent), keyed email / phone / address,
 * each with its value and a link (mailto:, tel:, or null for the address). Shared by the footer and Contact page.
 */
function contact_lines(): array
{
    $lines = [];
    foreach (['email', 'phone', 'address'] as $kind) {
        if (! $value = get_option("client_$kind")) {
            continue;
        }

        $lines[$kind] = ['value' => $value, 'href' => match ($kind) {
            'email' => "mailto:$value",
            'phone' => 'tel:' . preg_replace('/[^0-9+]/', '', $value),
            default => null,
        }];
    }

    return $lines;
}

/**
 * The social profiles a site has set (options `client_facebook`, `client_instagram`, `client_linkedin`, `client_x`, `client_youtube`, the
 * same `client_*` convention as contact_lines()), as [network => ['label' => ..., 'url' => ...]] in a fixed order. Only profiles with a
 * URL are listed; the footer prints icons for them through the pkde-kit::partials.social-links partial.
 */
function social_links(): array
{
    $networks = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'x' => 'X', 'youtube' => 'YouTube'];
    $links = [];

    foreach ($networks as $key => $label) {
        $url = trim((string) get_option("client_$key"));

        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $links[$key] = ['label' => $label, 'url' => $url];
        }
    }

    return $links;
}

/**
 * The meta field a WooCommerce sort choice orders by when Relevanssi answers the query (price, popularity, rating), or null.
 */
function search_sort_field(?string $orderby): ?string
{
    return ['price' => '_price', 'popularity' => 'total_sales', 'rating' => '_wc_average_rating'][$orderby ?? ''] ?? null;
}

/**
 * The header's breakpoint classes: from which Tailwind breakpoint the menu shows in the bar and the menu button disappears. Default `sm`
 * (the template's layout); a site with a long menu sets `add_filter('pkde_kit/nav_breakpoint', fn () => 'xl')` (sm, md, lg, xl or 2xl).
 * Every class is a literal string below on purpose: Tailwind only generates classes it can find as text in a scanned file.
 *
 * @return array{nav: string, hideAbove: string, gap: string, menuGap: string}
 */
function nav_classes(): array
{
    $sets = [
        'sm' => ['nav' => 'hidden sm:block', 'hideAbove' => 'sm:hidden', 'gap' => 'gap-4 sm:gap-8', 'menuGap' => 'sm:gap-8'],
        'md' => ['nav' => 'hidden md:block', 'hideAbove' => 'md:hidden', 'gap' => 'gap-4 md:gap-8', 'menuGap' => 'md:gap-8'],
        'lg' => ['nav' => 'hidden lg:block', 'hideAbove' => 'lg:hidden', 'gap' => 'gap-4 lg:gap-8', 'menuGap' => 'lg:gap-8'],
        'xl' => ['nav' => 'hidden xl:block', 'hideAbove' => 'xl:hidden', 'gap' => 'gap-4 xl:gap-8', 'menuGap' => 'xl:gap-8'],
        '2xl' => ['nav' => 'hidden 2xl:block', 'hideAbove' => '2xl:hidden', 'gap' => 'gap-4 2xl:gap-8', 'menuGap' => '2xl:gap-8'],
    ];
    $breakpoint = function_exists('apply_filters') ? (string) apply_filters('pkde_kit/nav_breakpoint', 'sm') : 'sm';

    return $sets[$breakpoint] ?? $sets['sm'];
}

/**
 * Does the header show the search icon that opens a panel under the bar? Opt-in: `add_filter('pkde_kit/header_search', '__return_true')`.
 */
function header_search_enabled(): bool
{
    return function_exists('apply_filters') && (bool) apply_filters('pkde_kit/header_search', false) && class_exists('WooCommerce');
}

/**
 * The full-size URL a block image should link or load: the attachment's own (absolute) URL when `$id` is a real
 * attachment, otherwise the stored URL made absolute.
 */
function block_image_url(int $id, ?string $url): string
{
    return absolute_url($id && get_post_type($id) === 'attachment' ? wp_get_attachment_url($id) : $url);
}

/**
 * An <img> for a block image. An attachment goes through wp_get_attachment_image() (absolute URL plus srcset, which
 * EWWW can turn into WebP); otherwise a plain <img> with the stored URL made absolute. Empty when there is neither.
 * `sizes` only means something alongside a srcset, so the plain <img> leaves it off.
 */
function block_image(int $id, ?string $url, string $alt, string $size, array $attributes = []): string
{
    if ($id && get_post_type($id) === 'attachment') {
        return wp_get_attachment_image($id, $size, false, $attributes + ['alt' => $alt]);
    }

    if (($url = absolute_url($url)) === '') {
        return '';
    }

    unset($attributes['sizes']);
    $html = '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '"';
    foreach ($attributes as $name => $value) {
        $html .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }

    return $html . ' />';
}

/**
 * 1 for the first hero slide printed in this request, 2 for the second, and so on. A named function, because a
 * `static` variable inside a block's render.php does not survive between slides: each include gets a fresh scope
 * (found the hard way on rp: the first attempt left every slide eager). Per request, so a page with two sliders
 * treats the second slider's first slide as a later one, which is right: only the first is above the fold.
 */
function next_hero_slide_number(): int
{
    static $number = 0;

    return ++$number;
}

/**
 * The `sizes` attribute for the header logo: how wide it is actually DRAWN (its height is fixed in CSS, see
 * --header-logo-h / --header-logo-h-sm in app.css, so the width follows from the image's aspect ratio), so the
 * browser picks a small srcset candidate instead of the full-width one. Empty when the image size is unknown.
 */
function logo_sizes(int $width, int $height, int $smallHeight = 32, int $largeHeight = 40): string
{
    if ($width <= 0 || $height <= 0) {
        return '';
    }

    return sprintf('(min-width: 640px) %dpx, %dpx', round($largeHeight * $width / $height), round($smallHeight * $width / $height));
}

/**
 * The classes of a content container the kit prints (the inner column of Hero and Hero Slide, the wrapper of a themed loop section,
 * the page header, post grid, single post and shop containers). A site whose container class is its own (a `.site-container` that
 * is not `mx-auto max-w-content px-[var(--page-gutter,1.5rem)]`) filters it instead of overriding the markup, so the page keeps the kit's structure:
 *
 *   add_filter('pkde_kit/container_class', fn ($class, $context) => $context === 'sage/hero' ? 'site-container' : $class, 10, 2);
 *
 * $context names the place: `sage/hero`, `sage/hero-slide`, `sage/loop-section`, `page-header`, `post-grid`,
 * `content-single`, `shop-archive`, `shop-archive-body`. A filter that returns anything but a string is
 * ignored. Tailwind only generates a class it can read in a source file, so a replacement must appear in the site's own files.
 */
function container_class(string $default = 'mx-auto w-full max-w-content px-[var(--page-gutter,1.5rem)]', string $context = ''): string
{
    $class = function_exists('apply_filters') ? apply_filters('pkde_kit/container_class', $default, $context) : $default;

    return is_string($class) && $class !== '' ? $class : $default;
}

/**
 * Output formats for generated image sub-sizes: PNG sub-sizes are saved as WebP (an uploaded logo-on-photo PNG's
 * medium_large size is hundreds of KB as PNG, a fraction of that as WebP). The uploaded original is never touched.
 */
function png_output_formats(array $formats): array
{
    return ['image/png' => 'image/webp'] + $formats;
}

/** Quality for the WebP sub-sizes WordPress generates (80 is visually lossless for photos and cards). */
function webp_quality(int $quality, string $mime): int
{
    return $mime === 'image/webp' ? 80 : $quality;
}

/**
 * Gives every <iframe> in trusted embed HTML a title when it has none or an empty one (WCAG 4.1.2, axe "frame-title"). A pasted
 * Google Maps embed has no title, and a screen reader then announces only "frame". Use it wherever a site prints embed HTML it
 * did not write: `{!! \App\iframe_titles($embed, __('Map of our location', 'sage')) !!}`. An iframe that already has a title is
 * left alone. The visible focus ring for an iframe is in app.css.
 */
function iframe_titles(string $html, string $title): string
{
    return preg_replace_callback('/<iframe\b([^>]*)>/i', function (array $m) use ($title): string {
        $attributes = $m[1];
        if (preg_match('/\stitle\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $attributes, $existing) && trim($existing[1] . ($existing[2] ?? '')) !== '') {
            return $m[0];
        }
        $attributes = preg_replace('/\stitle\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $attributes);

        return '<iframe title="' . htmlspecialchars($title, ENT_QUOTES) . '"' . $attributes . '>';
    }, $html) ?? $html;
}

/**
 * Is this a filtered view of a product list (a shop filter choice, a price range or a ?sage_view=sale|new)? Such URLs are
 * combinations of the same products, so they are served noindex, follow (app/woocommerce/filter-query.php).
 *
 * @param  array<string, mixed>  $query  the request's query arguments ($_GET)
 */
function is_faceted_request(array $query): bool
{
    foreach (array_keys($query) as $key) {
        if (in_array((string) $key, ['sage_view', 'min_price', 'max_price'], true) || str_starts_with((string) $key, 'sage_filter_')) {
            return true;
        }
    }

    return false;
}

if (! function_exists('add_filter')) {
    return; // loaded outside WordPress (unit tests): the functions above are all there is
}

/**
 * A photograph for a product category that has no thumbnail of its own: the most popular product in it (a product that is in
 * stock when there is one). Cached for a day; an empty string when there is none or WooCommerce is not active.
 */
function category_menu_image(int $termId): string
{
    $key = 'menu_image_' . $termId;
    $cached = get_transient($key);
    if ($cached !== false) {
        return (string) $cached;
    }

    $url = '';
    $term = get_term($termId, 'product_cat');
    if ($term instanceof \WP_Term && function_exists('wc_get_products')) {
        foreach (['instock', ''] as $stock) {
            $args = ['limit' => 1, 'status' => 'publish', 'category' => [$term->slug], 'orderby' => 'popularity', 'order' => 'DESC'] + ($stock ? ['stock_status' => $stock] : []);
            $product = wc_get_products($args)[0] ?? null;
            $url = $product ? (string) wp_get_attachment_image_url($product->get_image_id(), 'medium_large') : '';
            if ($url) {
                break;
            }
        }
    }
    set_transient($key, $url, DAY_IN_SECONDS);

    return $url;
}

/**
 * Stored page content (rendered once at import time, or typed in the editor) keeps root-relative upload URLs.
 * Make the <img> sources absolute on output so EWWW can serve WebP for them too.
 */
add_filter('the_content', static fn(string $content): string => preg_replace(
    '#(<img\b[^>]*\ssrc=")(/app/uploads/)#',
    '$1' . home_url('/app/uploads/'),
    $content,
) ?? $content, 20);

/**
 * Size the header's custom logo to its display width. The heights are the defaults of --header-logo-h and
 * --header-logo-h-sm (2rem and 2.5rem at a 16px root); a site that overrides those variables returns its own pixel
 * heights here: add_filter('pkde_header_logo_heights', fn() => [40, 48]);  [below sm, from sm]
 */
add_filter('get_custom_logo_image_attributes', static function (array $attributes, int $logoId): array {
    $meta = wp_get_attachment_metadata($logoId) ?: [];
    [$small, $large] = apply_filters('pkde_header_logo_heights', [32, 40]);

    if ($sizes = logo_sizes((int) ($meta['width'] ?? 0), (int) ($meta['height'] ?? 0), (int) $small, (int) $large)) {
        $attributes['sizes'] = $sizes;
    }

    return $attributes;
}, 10, 2);

/**
 * Generated PNG sub-sizes are saved as WebP at quality 80 (see png_output_formats). Applies to sub-sizes generated after
 * this ships; regenerate existing ones with `wp media regenerate --only-missing`. Opt out with
 * add_filter('pkde_png_to_webp', '__return_false'); the server's image editor must support WebP (WordPress checks).
 */
add_filter('image_editor_output_format', static fn(array $formats): array => apply_filters('pkde_png_to_webp', true) ? png_output_formats($formats) : $formats);
add_filter('wp_editor_set_quality', static fn($quality, $mime) => apply_filters('pkde_png_to_webp', true) ? webp_quality((int) $quality, (string) $mime) : $quality, 10, 2);
