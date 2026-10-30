<?php

// app/template-folders.php defines pure functions and returns early outside WordPress, so it loads here without WordPress.
require_once dirname(__DIR__, 2) . '/app/template-folders.php';

use function App\template_folder;
use function App\template_with_folders;

it('puts blog, shop and page templates in their folders, and leaves the rest flat', function () {
    expect(template_folder('category.php'))->toBe('blog')
        ->and(template_folder('category-news.php'))->toBe('blog')
        ->and(template_folder('tag.php'))->toBe('blog')
        ->and(template_folder('author-jane.php'))->toBe('blog')
        ->and(template_folder('home.php'))->toBe('blog')
        ->and(template_folder('archive.php'))->toBe('blog')
        ->and(template_folder('single.php'))->toBe('blog')
        ->and(template_folder('single-post.php'))->toBe('blog')
        ->and(template_folder('archive-product.php'))->toBe('shop')
        ->and(template_folder('single-product.php'))->toBe('shop')
        ->and(template_folder('taxonomy-product_cat.php'))->toBe('shop')
        ->and(template_folder('taxonomy-product_cat-shoes.php'))->toBe('shop')
        ->and(template_folder('taxonomy-product_tag.php'))->toBe('shop')
        ->and(template_folder('taxonomy-product_brand.php'))->toBe('shop')
        ->and(template_folder('page.php'))->toBe('pages')
        ->and(template_folder('page-about.php'))->toBe('pages')
        ->and(template_folder('front-page.php'))->toBe('pages')
        ->and(template_folder('404.php'))->toBe('pages')
        ->and(template_folder('search.php'))->toBe('pages')
        // stays flat: the catch-all, other taxonomies, a custom post type's own views
        ->and(template_folder('index.php'))->toBeNull()
        ->and(template_folder('taxonomy.php'))->toBeNull()
        ->and(template_folder('taxonomy-brand.php'))->toBeNull()
        ->and(template_folder('single-project.php'))->toBeNull()
        ->and(template_folder('archive-project.php'))->toBeNull();
});

it('adds the folder file right after the flat one, so a flat site view always wins', function () {
    expect(template_with_folders(['category-news.php', 'category.php', 'archive.php', 'index.php']))->toBe([
        'category-news.php', 'blog/category-news.php', 'category.php', 'blog/category.php', 'archive.php', 'blog/archive.php', 'index.php',
    ])
        ->and(template_with_folders(['taxonomy-product_cat.php', 'taxonomy.php', 'index.php']))->toBe(['taxonomy-product_cat.php', 'shop/taxonomy-product_cat.php', 'taxonomy.php', 'index.php']);
});

it('does not fold a candidate twice or touch one that already has a folder', function () {
    expect(template_with_folders(['blog/category.php', 'category.php', 'blog/category.php']))->toBe(['blog/category.php', 'category.php']);
});

it('registers on the hierarchy filters before Acorn, from the package autoload', function () {
    $source = file_get_contents(dirname(__DIR__, 2) . '/app/template-folders.php');
    $composer = file_get_contents(dirname(__DIR__, 2) . '/composer.json');

    expect($source)->toContain("template_hierarchy\", __NAMESPACE__ . '\\\\template_with_folders', 9)")
        ->and($composer)->toContain('app/template-folders.php');
});
