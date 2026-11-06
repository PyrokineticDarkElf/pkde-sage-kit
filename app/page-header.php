<?php

/**
 * The page header's optional intro line (owner via the manager, MGR-77): a short paragraph under the H1.
 *
 *   - the posts page (Settings > Reading > Posts page): that page's EXCERPT (written in the page's Excerpt box); nothing when it is empty;
 *   - a category, tag or other term archive: the term's description, EXCEPT on a product term whose "Read more about X" toggle already prints
 *     it (partials/shop-archive, switched by the pkde_kit/shop_term_toggle filter), so it never shows twice.
 *
 * `pkde_kit/page_header_intro` (string $html, mixed $queriedObject) can replace it or, by returning '', switch it off. The look is the
 * --pkde-page-header-intro* variables (resources/css/page-header.css).
 */

namespace App;

/**
 * What the intro depends on, read from WordPress once: the object being listed, whether this is the posts page and its excerpt, a term's
 * description and its HTML, and the filter function. page_header_intro() takes it as an argument so a test needs no WordPress.
 *
 * @return array{object: mixed, home: bool, excerpt: string, term: bool, description: string, html: callable, product: bool, filter: callable}
 */
function page_header_context(): array
{
    $object = get_queried_object();
    $page = is_home() ? (int) get_option('page_for_posts') : 0;
    $term = (is_category() || is_tag() || is_tax()) && $object instanceof \WP_Term;

    return [
        'object' => $object,
        'home' => is_home(),
        'excerpt' => $page ? trim((string) get_post_field('post_excerpt', $page)) : '',
        'term' => $term,
        'description' => $term ? trim((string) $object->description) : '',
        'html' => fn() => wp_kses_post(term_description($object)),
        'product' => function_exists('is_product_taxonomy') && is_product_taxonomy(),
        'filter' => 'apply_filters',
    ];
}

/**
 * The intro HTML for this request, '' for none.
 *
 * @param  array<string, mixed>|null  $ctx  page_header_context(); a test passes its own
 */
function page_header_intro(?array $ctx = null): string
{
    $ctx ??= page_header_context();
    $filter = $ctx['filter'];
    $intro = '';

    if ($ctx['home']) {
        $intro = $ctx['excerpt'] !== '' ? '<p>' . htmlspecialchars($ctx['excerpt'], ENT_QUOTES) . '</p>' : '';
    } elseif ($ctx['term'] && $ctx['description'] !== '') {
        // A product term's "Read more" toggle (partials/shop-archive) prints the same text: the header leaves it alone while the toggle shows.
        $toggleShows = $ctx['product'] && (bool) $filter('pkde_kit/shop_term_toggle', true, $ctx['object']);
        $intro = $toggleShows ? '' : $ctx['html']();
    }

    return trim((string) $filter('pkde_kit/page_header_intro', $intro, $ctx['object']));
}
