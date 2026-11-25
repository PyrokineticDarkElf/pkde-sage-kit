<?php

/**
 * The kit's block patterns (WP-100): resources/patterns/*.php, registered under the `pkde` category (sections) and `pkde-pages` (page starters).
 * A pattern file is a PHP file with a header like a theme pattern's (Title, Slug, Categories, Block Types, Post Types, Description, Keywords, Inserter)
 * followed by block markup; its outermost block is a Section locked to content only (templateLock contentOnly), so a client edits the words,
 * pictures and links and cannot move, add or delete blocks inside it. Only an Administrator can unlock it (platform: editor-settings).
 *
 * A site's own patterns go in its theme's patterns/ folder: WordPress registers those by itself.
 */

namespace App;

/** The header fields of a pattern file, as the arguments register_block_pattern() takes (without the content). */
function pattern_header(string $file): array
{
    $data = function_exists('get_file_data')
        ? get_file_data($file, ['title' => 'Title', 'slug' => 'Slug', 'description' => 'Description', 'categories' => 'Categories', 'keywords' => 'Keywords', 'blockTypes' => 'Block Types', 'postTypes' => 'Post Types', 'inserter' => 'Inserter'])
        : [];
    $list = fn(string $value) => array_values(array_filter(array_map('trim', explode(',', $value))));

    return [
        'slug' => (string) ($data['slug'] ?? ''),
        'title' => (string) ($data['title'] ?? ''),
        'description' => (string) ($data['description'] ?? ''),
        'categories' => $list((string) ($data['categories'] ?? '')),
        'keywords' => $list((string) ($data['keywords'] ?? '')),
        'blockTypes' => $list((string) ($data['blockTypes'] ?? '')),
        'postTypes' => $list((string) ($data['postTypes'] ?? '')),
        'inserter' => strtolower((string) ($data['inserter'] ?? 'yes')) !== 'no',
    ];
}

/**
 * The families of the pattern bank (WP-108 B): the pattern category slug (after `pkde-`) and its label. A pattern is "Family: Variant" and has the one category of its family.
 *
 * @return array<string, string>
 */
function pattern_families(): array
{
    return [
        'hero' => __('Hero', 'sage'),
        'featured-products' => __('Featured products', 'sage'),
        'categories' => __('Product categories', 'sage'),
        'latest-posts' => __('Latest posts', 'sage'),
        'features' => __('Feature grid', 'sage'),
        'text-image' => __('Text and image', 'sage'),
        'testimonials' => __('Testimonials', 'sage'),
        'cta' => __('Call to action', 'sage'),
        'faq' => __('FAQ', 'sage'),
        'contact' => __('Contact', 'sage'),
        'stats' => __('Stats', 'sage'),
        'logos' => __('Logos', 'sage'),
    ];
}

if (! function_exists('add_action')) {
    return;
}

add_action('init', function () {
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('pkde', ['label' => __('Sections', 'sage')]);
    // One category per family (WP-108 B), all under the kit's `pkde-` prefix, so the inserter groups the bank by what a section is for.
    foreach (pattern_families() as $slug => $label) {
        register_block_pattern_category("pkde-{$slug}", ['label' => $label]);
    }
    register_block_pattern_category('pkde-pages', ['label' => __('Page starters', 'sage')]);

    foreach (glob(dirname(__DIR__) . '/resources/patterns/*.php') ?: [] as $file) {
        $header = pattern_header($file);

        if ($header['slug'] === '' || $header['title'] === '') {
            continue;
        }

        ob_start();
        include $file;
        $content = trim((string) ob_get_clean());

        if ($content === '') {
            continue;
        }

        register_block_pattern($header['slug'], array_filter(['title' => $header['title'], 'description' => $header['description'], 'categories' => $header['categories'], 'keywords' => $header['keywords'], 'blockTypes' => $header['blockTypes'], 'postTypes' => $header['postTypes'], 'inserter' => $header['inserter'], 'content' => $content], fn($value) => $value !== [] && $value !== ''));
    }
});
