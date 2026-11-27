<?php

/**
 *: the filter panel is one <search> landmark named by its own <h2>; the recently viewed box is an <aside> outside it; the "Read more about" /
 * "About" label in a term toggle's <summary> is a <span>, not a heading (a <summary> is a button, its children are presentational).
 */
$root = dirname(__DIR__, 2);

it('wraps the filters title and form in a <search> named by the heading, and keeps the recently viewed box outside it', function () use ($root) {
    $body = file_get_contents("$root/resources/views/partials/shop-archive-body.blade.php");
    $sidebar = file_get_contents("$root/resources/views/partials/sidebar-shop.blade.php");

    expect($body)->toContain('<search class="pkde-shop-search"', 'aria-labelledby="shop-filters-title"', '<h2 id="shop-filters-title" class="shop-archive__filters-title">')
        ->and(strpos($body, '<search'))->toBeLessThan(strpos($body, 'shop-filters-title" class'))
        ->and(strpos($body, 'shop-filters-title" class'))->toBeLessThan(strpos($body, "partials.sidebar-shop"))
        ->and(strpos($body, "partials.sidebar-shop"))->toBeLessThan(strpos($body, '</search>'))
        ->and(strpos($body, '</search>'))->toBeLessThan(strpos($body, 'recently_viewed_box()'))
        ->and($body)->toContain('aria-label="{{ esc_attr__(\'Filters\'') // no heading (the filter removes it): still named
        ->and($sidebar)->not->toContain('<aside')->not->toContain('recently_viewed_box()')
        ->and($sidebar)->toContain('<div' . "\n" . '  id="shop-filters"')
        ->and(file_get_contents("$root/resources/views/partials/recently-viewed.blade.php"))->toContain('<aside class="pkde-recent"', 'aria-labelledby="pkde-recent-title"');
});

it('keeps the sticky column layout with the <search> as the flex child, and the recently viewed box following the toggle', function () use ($root) {
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($css)->toContain('.shop-archive__filters > search,')->toContain('.shop-archive__filters > search {')
        // the plain display rule comes before the sticky media query, so the flex rule inside it wins
        ->and(strpos($css, ".shop-archive__filters > search {\n  display: block"))->toBeLessThan(strpos($css, '@media (min-width: 1024px) and (min-height: 820px)'))
        ->and(file_get_contents("$root/resources/js/kit.js"))->toContain('[data-filter-follow]')
        // the recently viewed list keeps the look it had inside the panel (its list rules and the utility classes the panel carried)
        ->and($css)->toContain('#shop-recent ul')
        ->and(file_get_contents("$root/resources/views/partials/shop-archive-body.blade.php"))->toContain('id="shop-recent"', '[&_ul]:text-sm', '[&_a]:text-neutral-600');
});

it('prints the term toggle label as a span, not a heading, and styles it like the heading it replaced', function () use ($root) {
    $display = file_get_contents("$root/app/woocommerce/display.php");
    $archive = file_get_contents("$root/resources/views/partials/shop-archive.blade.php");
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($display)->toContain("'<span>' . esc_html(sprintf(__('About %s', 'sage')")->not->toContain("'<h2>' . esc_html(sprintf(__('About")
        ->and($archive)->toContain("'<span>' . esc_html(sprintf(__('Read more about %s'")->not->toContain("'<h2>' . esc_html(sprintf(__('Read more")
        ->and($css)->toContain('.pkde-term-description summary > :where(span, h2, h3, h4) {');
});
