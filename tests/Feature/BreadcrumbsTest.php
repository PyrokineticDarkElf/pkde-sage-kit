<?php

// app/breadcrumbs.php: the kit owns the breadcrumb trail (MGR-77 parity). The standard, the markup and the fallback crumbs need no WordPress.
require_once dirname(__DIR__, 2) . '/app/breadcrumbs.php';

use function App\breadcrumb_crumbs;
use function App\breadcrumb_markup;
use function App\breadcrumb_standard;

$root = dirname(__DIR__, 2);

it('forces one standard on Rank Math: " / ", Home, the page as the last crumb, ancestors and the blog shown', function () {
    $standard = breadcrumb_standard();

    expect($standard['settings'])->toMatchArray(['home' => true, 'separator' => '/', 'remove_title' => false, 'show_ancestors' => true, 'show_blog' => true])
        ->and($standard['strings'])->toMatchArray(['prefix' => '', 'home' => 'Home', 'error404' => '404 Error: page not found', 'search_format' => 'Results for %s', 'archive_format' => 'Archives for %s']);
});

it('builds the same markup as Rank Math: a nav, links, " / " separators and a last span', function () {
    $html = breadcrumb_markup([['Home', 'https://example.test/'], ['Blog', 'https://example.test/blog/'], ['Ten & tips', '']]);

    expect($html)->toBe('<nav aria-label="breadcrumbs" class="rank-math-breadcrumb"><p><a href="https://example.test/">Home</a><span class="separator"> / </span><a href="https://example.test/blog/">Blog</a><span class="separator"> / </span><span class="last">Ten &amp; tips</span></p></nav>')
        ->and(breadcrumb_markup([]))->toBe('');
});

it('builds the fallback trail for each kind of page, and none on the front page', function () {
    $home = ['Home', 'https://example.test/'];
    $blog = ['Blog', 'https://example.test/blog/'];

    expect(breadcrumb_crumbs(['type' => 'front', 'home' => $home]))->toBe([])
        ->and(breadcrumb_crumbs(['type' => 'home', 'home' => $home, 'blog' => $blog, 'title' => 'Blog']))->toBe([$home, ['Blog', '']])
        ->and(breadcrumb_crumbs(['type' => 'post', 'home' => $home, 'blog' => $blog, 'ancestors' => [['News', 'https://example.test/news/']], 'title' => 'Ten tips']))
        ->toBe([$home, $blog, ['News', 'https://example.test/news/'], ['Ten tips', '']])
        ->and(breadcrumb_crumbs(['type' => 'term', 'home' => $home, 'blog' => $blog, 'with_blog' => false, 'ancestors' => [['Shop', 'https://example.test/shop/']], 'title' => 'Clocks']))
        ->toBe([$home, ['Shop', 'https://example.test/shop/'], ['Clocks', '']])
        ->and(breadcrumb_crumbs(['type' => 'page', 'home' => $home, 'blog' => $blog, 'ancestors' => [['About', 'https://example.test/about/']], 'title' => 'Team']))
        ->toBe([$home, ['About', 'https://example.test/about/'], ['Team', '']])
        ->and(breadcrumb_crumbs(['type' => 'search', 'home' => $home, 'title' => 'Results for clocks']))->toBe([$home, ['Results for clocks', '']])
        ->and(breadcrumb_crumbs(['type' => '404', 'home' => $home, 'title' => '404 Error: page not found']))->toBe([$home, ['404 Error: page not found', '']]);
});

it('adds the page number on a paged archive and links the archive to page one', function () {
    $crumbs = breadcrumb_crumbs(['type' => 'home', 'home' => ['Home', '/'], 'title' => 'Blog', 'url' => 'https://example.test/blog/', 'page' => 3, 'page_label' => 'Page %d']);

    expect($crumbs)->toBe([['Home', '/'], ['Blog', 'https://example.test/blog/'], ['Page 3', '']]);
});

it('wires the standard into Rank Math, forces its module on, and prints the trail from one partial', function () use ($root) {
    $code = file_get_contents("$root/app/breadcrumbs.php");
    $partial = file_get_contents("$root/resources/views/partials/breadcrumbs.blade.php");

    expect($code)->toContain("'rank_math/frontend/breadcrumb/settings'", "'rank_math/frontend/breadcrumb/strings'", "add_theme_support('rank-math-breadcrumbs')", "'pkde_kit/force_rank_math_breadcrumbs'", "'pkde_kit/breadcrumb_standard'", "'pkde_kit/breadcrumb_html'")
        ->and($partial)->toContain('\App\breadcrumb_trail_html()')->not->toContain('woocommerce_breadcrumb(')
        ->and(file_get_contents("$root/app/helpers.php"))->toContain("require_once __DIR__ . '/breadcrumbs.php';");
});
