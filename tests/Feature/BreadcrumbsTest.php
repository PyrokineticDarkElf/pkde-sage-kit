<?php

// app/breadcrumbs.php: the kit owns the breadcrumb standard (MGR-77 parity). The standard needs no WordPress.
require_once dirname(__DIR__, 2) . '/app/breadcrumbs.php';

use function App\breadcrumb_standard;

$root = dirname(__DIR__, 2);

it('forces one standard on Rank Math: " / ", Home, the page as the last crumb, ancestors and the blog shown', function () {
    $standard = breadcrumb_standard();

    expect($standard['settings'])->toMatchArray(['home' => true, 'separator' => '/', 'remove_title' => false, 'show_ancestors' => true, 'show_blog' => true])
        ->and($standard['strings'])->toMatchArray(['prefix' => '', 'home' => 'Home', 'error404' => '404 Error: page not found', 'search_format' => 'Results for %s', 'archive_format' => 'Archives for %s']);
});

it('wires the standard into Rank Math, forces its module on, and prints the trail from one partial', function () use ($root) {
    $code = file_get_contents("$root/app/breadcrumbs.php");
    $partial = file_get_contents("$root/resources/views/partials/breadcrumbs.blade.php");

    expect($code)->toContain("'rank_math/frontend/breadcrumb/settings'", "'rank_math/frontend/breadcrumb/strings'", "add_theme_support('rank-math-breadcrumbs')", "'pkde_kit/force_rank_math_breadcrumbs'", "'pkde_kit/breadcrumb_standard'", "'pkde_kit/breadcrumb_html'")
        ->and($partial)->toContain('\App\breadcrumb_trail_html()')
        ->and($code)->toContain("'delimiter' => ' / '")->not->toContain('function breadcrumb_crumbs')->not->toContain('function breadcrumb_markup')
        ->and(file_get_contents("$root/app/helpers.php"))->toContain("require_once __DIR__ . '/breadcrumbs.php';");
});
