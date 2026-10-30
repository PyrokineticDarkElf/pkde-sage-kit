<?php

/**
 * Theme views grouped into folders by use (MGR owner request via tcs, TPL-140): resources/views/blog/, shop/ and pages/ hold the default views for
 * the blog (home, archive, category, tag, author, single), the shop (archive-product, the product category, tag and brand archives,
 * single-product) and pages (page, front-page, 404, search). WordPress still names the templates it wants by the hierarchy
 * (`category.php`, `taxonomy-product_cat.php` ...); Acorn turns each name into a view file. This adds the folder file right after the flat one:
 *
 *   category.php, blog/category.php, archive.php, blog/archive.php, index.php
 *
 * so a site's flat view (resources/views/category.blade.php) always wins over the folder default, and a site overrides ONE archive by adding one
 * file. Page templates chosen in the editor (`template-about.blade.php`, "Template Name:") are not part of the hierarchy and stay where they are.
 * Runs at priority 9, before Acorn's own `*_template_hierarchy` filter (10) turns the list into view files.
 */

namespace App;

/** The folder a hierarchy candidate (such as `taxonomy-product_cat-shoes.php`) belongs to, or null for one that stays flat (index.php, a custom post type's own). */
function template_folder(string $file): ?string
{
    $name = basename($file, '.php');

    return match (true) {
        (bool) preg_match('/^(archive-product|single-product|taxonomy-product_(?:cat|tag|brand))(?:-.+)?$/', $name) => 'shop',
        (bool) preg_match('/^(?:home|archive|date|single|single-post)$|^(?:category|tag|author)(?:-.+)?$/', $name) => 'blog',
        (bool) preg_match('/^(?:page|front-page|404|search)(?:-.+)?$/', $name) => 'pages',
        default => null,
    };
}

/**
 * The hierarchy with each candidate's folder file added right after it.
 *
 * @param  list<string>  $templates
 * @return list<string>
 */
function template_with_folders(array $templates): array
{
    $out = [];

    foreach ($templates as $template) {
        $out[] = $template;
        $folder = is_string($template) && ! str_contains($template, '/') ? template_folder($template) : null;

        if ($folder !== null) {
            $out[] = "$folder/$template";
        }
    }

    return array_values(array_unique($out));
}

if (! function_exists('add_filter')) {
    return;
}

foreach (['404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home', 'frontpage', 'page', 'paged', 'search', 'single', 'singular'] as $type) {
    add_filter("{$type}_template_hierarchy", __NAMESPACE__ . '\\template_with_folders', 9);
}
