<?php

/**
 * The blog single (tcs's layout as the kit default, owner via MGR-66): helpers for resources/views/partials/content-single and its parts. Pure
 * where it can be (the schema and the share links are built from plain values), so a test needs no WordPress.
 *
 * Sections can be switched off one by one with `pkde_kit/single_show` (bool $show, string $section, ?WP_Post $post); the sections are
 * breadcrumbs, categories, meta, hero, toc, share, tags, author_box, navigation, related, recent, schema. Actions print inside the layout:
 * pkde_kit/single_before_hero, _after_hero, _before_meta, _after_meta, _before_content, _after_content, _before_sidebar, _after_sidebar,
 * _before_related, _after_related (each gets the WP_Post). See docs/blog-single.md.
 */

namespace App;

/** The sections of the single post layout, in the order they appear. */
const SINGLE_SECTIONS = ['breadcrumbs', 'categories', 'meta', 'hero', 'toc', 'share', 'tags', 'author_box', 'navigation', 'related', 'recent', 'schema'];

/** Whether a section of the single post layout is shown (`pkde_kit/single_show`). */
function single_show(string $section, $post = null): bool
{
    return ! function_exists('apply_filters') || (bool) apply_filters('pkde_kit/single_show', true, $section, $post);
}

/**
 * Whether a byline links to the author's archive: only while the archive exists (Rank Math can switch author archives off and redirect them to
 * the home page). `pkde_kit/author_archives` (bool) overrides.
 */
function byline_links_to_archive(): bool
{
    $on = function_exists('get_option') ? (get_option('rank-math-options-titles')['disable_author_archives'] ?? 'off') !== 'on' : true;

    return ! function_exists('apply_filters') || (bool) apply_filters('pkde_kit/author_archives', $on);
}

/**
 * schema.org Article for a post, from plain facts: title, url, published and modified (ISO 8601), author name and url, image url, site name and
 * logo url, description. Empty values are left out. `pkde_kit/single_schema` may change it or return an empty array to print nothing.
 *
 * @param  array<string, string>  $f
 * @return array<string, mixed>
 */
function article_schema(array $f): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $f['title'] ?? '',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $f['url'] ?? ''],
        'datePublished' => $f['published'] ?? '',
        'dateModified' => $f['modified'] ?? ($f['published'] ?? ''),
        'description' => $f['description'] ?? '',
        'image' => $f['image'] ?? '',
        'author' => ($f['author'] ?? '') !== '' ? array_filter(['@type' => 'Person', 'name' => $f['author'], 'url' => $f['author_url'] ?? '']) : null,
        'publisher' => ($f['site'] ?? '') !== '' ? array_filter(['@type' => 'Organization', 'name' => $f['site'], 'logo' => ($f['logo'] ?? '') !== '' ? ['@type' => 'ImageObject', 'url' => $f['logo']] : null]) : null,
    ];

    return array_filter($schema, fn($value) => $value !== '' && $value !== null && $value !== []);
}

/**
 * The share row: plain links to each network's share page (no script, no tracker from this site) and an email link. The "copy link" button is
 * drawn by the view. `pkde_kit/share_links` (array label => url) may add, remove or reorder.
 *
 * @return array<string, string>
 */
function share_links(string $url, string $title): array
{
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $links = [
        'Facebook' => "https://www.facebook.com/sharer/sharer.php?u=$u",
        'X' => "https://twitter.com/intent/tweet?url=$u&text=$t",
        'LinkedIn' => "https://www.linkedin.com/sharing/share-offsite/?url=$u",
        'WhatsApp' => "https://wa.me/?text=$t%20$u",
        'Email' => "mailto:?subject=$t&body=$u",
    ];

    return function_exists('apply_filters') ? (array) apply_filters('pkde_kit/share_links', $links, $url, $title) : $links;
}

/** The JSON for a <script type="application/ld+json">: safe inside a script element (no "</script>", no raw "<"). */
function schema_json(array $schema): string
{
    return (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/** The ids the related list of this request printed (set by partials/related-posts), so the recent list does not repeat them. */
function related_post_ids(?array $set = null): array
{
    static $ids = [];

    if ($set !== null) {
        $ids = array_map('intval', $set);
    }

    return $ids;
}

/**
 * The Article schema of the post in the loop (`pkde_kit/single_schema` may change it or return an empty array), or an empty array. Reads the
 * WordPress template tags; article_schema() is the pure part.
 *
 * @return array<string, mixed>
 */
function current_article_schema(): array
{
    $schema = article_schema([
        'title' => wp_strip_all_tags(get_the_title()),
        'url' => get_permalink(),
        'published' => get_post_time('c', true),
        'modified' => get_post_modified_time('c', true),
        'description' => wp_strip_all_tags(get_the_excerpt()),
        'image' => has_post_thumbnail() ? (string) get_the_post_thumbnail_url(null, 'large') : '',
        'author' => get_the_author(),
        'author_url' => byline_links_to_archive() ? get_author_posts_url((int) get_the_author_meta('ID')) : '',
        'site' => get_bloginfo('name'),
        'logo' => (string) wp_get_attachment_image_url((int) get_theme_mod('custom_logo'), 'full'),
    ]);

    return (array) apply_filters('pkde_kit/single_schema', $schema, get_post());
}
