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
 * oEmbed HTML for a URL, cached. wp_oembed_get() fetches the provider's endpoint over HTTP on EVERY call (WordPress caches only what
 * the_content's own embeds store in post meta), so a template that embeds a video on each page view makes a remote request per visit: slow
 * for the visitor, and a way to tie up PHP workers when the provider is slow. This keeps the HTML (12 hours) and, for a failed fetch, an
 * empty result for 10 minutes so a down provider is not retried on every request. The key is the URL and the arguments; a URL that is not
 * http(s) is never fetched. Returns '' when there is nothing to embed.
 *
 * @param  array<string, mixed>  $args  passed to wp_oembed_get() (width, height, ...)
 */
function cached_oembed(string $url, array $args = []): string
{
    $url = trim($url);

    if (! preg_match('#^https?://#i', $url) || ! function_exists('wp_oembed_get')) {
        return '';
    }

    $key = 'pkde_oembed_' . md5($url . serialize($args));
    $cached = get_transient($key);

    if (is_string($cached)) {
        return $cached;
    }

    $html = wp_oembed_get($url, $args);
    $html = is_string($html) ? $html : '';

    set_transient($key, $html, $html === '' ? 10 * MINUTE_IN_SECONDS : 12 * HOUR_IN_SECONDS);

    return $html;
}

/**
 * The site's contact details that are set, keyed email / phone / address, each with its value and a link (mailto:, tel:, or the map link / null for the address). Shared by the
 * footer and the Contact page. The values are Site details (Settings > Site details, the platform's site-details module: the FIRST phone, email and address); a site without
 * the module reads its `client_*` options, as it always did (WP-106).
 */
function contact_lines(): array
{
    $lines = [];

    if (($email = (string) (site_emails()[0]['address'] ?? '')) !== '') {
        $lines['email'] = ['value' => $email, 'href' => "mailto:$email"];
    }

    if ($phone = site_phones()[0] ?? null) {
        $lines['phone'] = ['value' => ($phone['display'] ?? '') !== '' ? $phone['display'] : $phone['number'], 'href' => site_tel_href($phone['number'])];
    }

    if (($place = site_locations()[0] ?? null) && ($address = implode(', ', site_address_lines($place))) !== '') {
        $lines['address'] = ['value' => $address, 'href' => ($place['map_url'] ?? '') !== '' ? $place['map_url'] : null];
    }

    return $lines;
}

/**
 * The social profiles a site has set, as [network => ['network' => ..., 'label' => ..., 'url' => ...]] in the order they were set (Settings > Site details; a site without the
 * platform module: the `client_*` options in the footer's fixed order). Only profiles with a web address are listed; a network set twice is keyed `network#2`. The footer prints
 * icons for them through the pkde-kit::partials.social-links partial.
 */
function social_links(): array
{
    $links = [];

    foreach (site_social_list((array) site_detail('socials', [])) as $social) {
        $key = isset($links[$social['network']]) ? $social['network'] . '#' . (count($links) + 1) : $social['network'];
        $links[$key] = $social;
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
 * block_image(), or a neutral placeholder picture when there is none and the block asks for one (`placeholder` on a block of a pattern, WP-108 B): a pattern cannot carry a
 * site's own pictures, so it draws a frame in the section's colours until the editor chooses one. Without `placeholder` nothing changes: empty stays empty.
 */
function block_media(int $id, ?string $url, string $alt, string $size, array $attributes = [], bool $placeholder = false): string
{
    $html = block_image($id, $url, $alt, $size, $attributes);

    if ($html !== '' || ! $placeholder) {
        return $html;
    }

    return '<span class="pkde-placeholder" role="img" aria-label="' . esc_attr__('Picture placeholder: choose a picture', 'sage') . '"></span>';
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
 * Is this a filtered or sorted view of a product list (a shop filter choice, a price range, a sort order or a
 * ?sage_view=sale|new)? Such URLs are combinations of the same products, so they are served noindex, follow: as an
 * X-Robots-Tag header, as a wp_robots tag and in Rank Math's robots meta (app/woocommerce/filter-query.php). This is the
 * ONE place that says which query arguments make a URL a faceted one.
 *
 * @param  array<string, mixed>  $query  the request's query arguments ($_GET)
 */
function is_faceted_request(array $query): bool
{
    foreach (array_keys($query) as $key) {
        if (in_array((string) $key, ['sage_view', 'min_price', 'max_price', 'orderby'], true) || str_starts_with((string) $key, 'sage_filter_')) {
            return true;
        }
    }

    return false;
}

/**
 * The Product schema offers with the prices a shopper SEES (WP-91, S-1): Rank Math prints the stored price, so a shop that displays prices with tax
 * (or without) while it stores them the other way round showed one price on the page and another in the markup. Offer: price, and the price and
 * valueAddedTaxIncluded of its priceSpecification. AggregateOffer: lowPrice and highPrice. A price that is null is left as Rank Math printed it.
 *
 * @param  array<string, mixed>  $offers  the entity's `offers`
 * @param  bool  $vatIncluded  the shop displays prices including tax
 * @return array<string, mixed>
 */
function schema_offer_prices(array $offers, ?string $price, ?string $low, ?string $high, bool $vatIncluded): array
{
    if (($offers['@type'] ?? '') === 'AggregateOffer') {
        return array_replace($offers, array_filter(['lowPrice' => $low, 'highPrice' => $high], fn($v) => $v !== null));
    }

    if ($price !== null) {
        $offers['price'] = $price;
    }

    if (isset($offers['priceSpecification']) && is_array($offers['priceSpecification'])) {
        $offers['priceSpecification'] = array_replace($offers['priceSpecification'], array_filter(['price' => $price], fn($v) => $v !== null), ['valueAddedTaxIncluded' => $vatIncluded ? 'true' : 'false']);
    }

    return $offers;
}

/**
 * schema.org OfferShippingDetails from what the store has set: a rate and the countries it applies to. Nothing when there is no rate.
 *
 * @param  list<string>  $countries  ISO country codes
 * @return array<string, mixed>
 */
function shipping_details_schema(?string $rate, string $currency, array $countries): array
{
    if ($rate === null || $currency === '' || $countries === []) {
        return [];
    }

    return [
        '@type' => 'OfferShippingDetails',
        'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => $rate, 'currency' => $currency],
        'shippingDestination' => array_map(fn($c) => ['@type' => 'DefinedRegion', 'addressCountry' => $c], array_values($countries)),
    ];
}

/**
 * Rank Math's robots directives with a faceted view made `noindex, follow`; other directives and other URLs unchanged.
 *
 * @param  array<string, string>  $robots  Rank Math's directives, keyed (index, follow, max-snippet, ...)
 * @param  array<string, mixed>  $query  the request's query arguments ($_GET)
 * @return array<string, string>
 */
function faceted_rank_math_robots(array $robots, array $query): array
{
    return is_faceted_request($query) ? ['index' => 'noindex', 'follow' => 'follow'] + $robots : $robots;
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
            $args = ['limit' => 1, 'status' => 'publish', 'visibility' => 'catalog', 'category' => [$term->slug], 'orderby' => 'popularity', 'order' => 'DESC'] + ($stock ? ['stock_status' => $stock] : []);
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
// WP-105 phase 1: pkde/platform's `media` module does this now (it defines PKDE_PLATFORM_MEDIA); while it is on, this copy is skipped, and PKDE_PLATFORM_DISABLE=media brings it back.
if (! defined('PKDE_PLATFORM_MEDIA')) {
    add_filter('the_content', static fn(string $content): string => preg_replace(
        '#(<img\b[^>]*\ssrc=")(/app/uploads/)#',
        '$1' . home_url('/app/uploads/'),
        $content,
    ) ?? $content, 20);
}

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
if (! defined('PKDE_PLATFORM_MEDIA')) { // WP-105 phase 1: the platform's `media` module does this when it is on
    add_filter('image_editor_output_format', static fn(array $formats): array => apply_filters('pkde_png_to_webp', true) ? png_output_formats($formats) : $formats);
    add_filter('wp_editor_set_quality', static fn($quality, $mime) => apply_filters('pkde_png_to_webp', true) ? webp_quality((int) $quality, (string) $mime) : $quality, 10, 2);
}

/**
 * The tax_query clause that leaves out products WooCommerce hides from the catalogue (catalogue visibility "hidden" or "search only"; they carry the
 * `exclude-from-catalog` term of `product_visibility`). WP_Query and get_posts() do not do this on their own, only WooCommerce's main shop query does:
 * every kit query that lists or counts products for visitors adds this (shop filter counts, the newest and best-selling blocks, the "new" badge rule).
 *
 * @return array{taxonomy: string, field: string, terms: list<string>, operator: string}
 */
function catalog_visibility_clause(): array
{
    return ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude-from-catalog'], 'operator' => 'NOT IN'];
}

/**
 * The views tried, in order, for one result card in partials/post-grid (search, post-type archives): a site's `partials/card-{type}`, then its
 * `partials/content-{type}`, then the kit's card. `content-page`, `content-page-wide`, `content-single` and `content-search` are the kit's own
 * page and single views (the kit's views root is on every theme's view finder), so `partials.content-page` always resolves and printed a whole
 * page for every page in the results (BP-78); a post type with one of those names never tries `partials/content-{type}`.
 *
 * @return list<string>
 */
function post_card_views(string $type): array
{
    $kitViews = ['page', 'page-wide', 'single', 'search'];

    return array_values(array_filter([
        'partials.card-' . $type,
        in_array($type, $kitViews, true) ? null : 'partials.content-' . $type,
        'pkde-kit::partials.content',
    ]));
}

/**
 * The upload URL of an original from the URL of one of its resized copies (".../clock-300x200.jpg" becomes ".../clock.jpg"). Pure.
 */
function strip_image_size(string $url): string
{
    return (string) preg_replace('/-\d+x\d+(?=\.\w+$)/', '', $url);
}

/**
 * An image given as a URL, printed through the media library when it is one of its files (TPL-193, from tcs TCS-84): an upload or a resized copy of one gets its
 * width and height (no layout shift), srcset and sizes; any other URL stays a plain lazy <img>. The URL may be site-relative ("/app/uploads/x.jpg"). Empty URL: ''.
 *
 * @param  array<string, string>  $attributes  extra attributes (class, sizes, alt)
 */
function media_library_image(string $url, string $alt = '', array $attributes = [], string $size = 'medium_large'): string
{
    if ($url === '') {
        return '';
    }

    $full = str_starts_with($url, '/') ? home_url($url) : $url;
    $id = attachment_url_to_postid($full) ?: attachment_url_to_postid(strip_image_size($full));
    $attributes = ['loading' => 'lazy', 'alt' => $alt] + $attributes;

    if ($id) {
        return (string) wp_get_attachment_image($id, $size, false, $attributes);
    }

    $html = '';
    foreach ($attributes as $name => $value) {
        $html .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }

    return '<img src="' . esc_url($full) . '"' . $html . ' />';
}

require_once __DIR__ . '/page-header.php';

require_once __DIR__ . '/single-post.php';

require_once __DIR__ . '/breadcrumbs.php';

require_once __DIR__ . '/tile-image.php';

/**
 * The ID of the Contact Form 7 form tagged with a lookup key (TPL-186, WP-83), 0 when no form has it. The key lives on the form post as meta
 * `_pkde_form_key`, so a theme finds its form without relying on the title (a rename never breaks it) or on an ID (IDs differ between staging and
 * production). `contact` is the standard key of the contact page's form; a site may add its own. bin/deploy/cf7-form-key.php (pkde-deploy) tags the
 * forms of an existing site; bin/setup tags a new site's. Cached for the request.
 */
function form_id(string $key = 'contact'): int
{
    // WP-105 phase 1: pkde/platform's `cf7-form-key` module answers (same meta key, same result); this is the fallback when it is off.
    if (defined('PKDE_PLATFORM_CF7_FORM_KEY') && function_exists('App\\MuPlugins\\cf7_form_id')) {
        return \App\MuPlugins\cf7_form_id($key);
    }

    static $found = [];

    if (! isset($found[$key])) {
        $ids = function_exists('get_posts') ? get_posts([
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'any',
            'numberposts' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_key' => '_pkde_form_key',
            'meta_value' => $key,
            'no_found_rows' => true,
        ]) : [];
        $found[$key] = (int) ($ids[0] ?? 0);
    }

    return $found[$key];
}

/** The `[contact-form-7 id="N"]` shortcode of the form with that key, or '' when the plugin is off or no form has the key. */
function form_shortcode(string $key = 'contact'): string
{
    $id = form_id($key);

    return $id > 0 && function_exists('shortcode_exists') && shortcode_exists('contact-form-7') ? '[contact-form-7 id="' . $id . '"]' : '';
}

/**
 * The padding classes of a page's content wrapper (TPL-278). A page of prose breathes (4rem above and below); a page that starts or ends with a full-width
 * Section, Group or band owns its own spacing, so the wrapper adds none there (it showed a band of the page background between the header and the first
 * section). A block is full-width when it carries `align: full`, is a Section (always full), or is a block that is always printed full (the CTA band).
 * `$blocks` is `parse_blocks()` output; empty blocks (whitespace between blocks) are skipped.
 */
function page_content_padding(array $blocks): string
{
    $blocks = array_values(array_filter($blocks, static fn ($block) => ! empty($block['blockName'])));
    $full = static fn (?array $block) => $block !== null
        && (($block['attrs']['align'] ?? '') === 'full' || in_array($block['blockName'], ['sage/section', 'sage/cta-band'], true));

    return ($full($blocks[0] ?? null) ? 'pt-0' : 'pt-16') . ' ' . ($full($blocks[count($blocks) - 1] ?? null) ? 'pb-0' : 'pb-16');
}
