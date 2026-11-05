<?php

/**
 * The blog archives and the shop archive views are the kit's (MGR-77): a theme without a file of that name gets these, and the blog pages
 * paginate with numbers in the shop's look. The tests read the files.
 */
$root = dirname(__DIR__, 2);

it('supplies a default view for every blog and shop archive', function () use ($root) {
    foreach (['home', 'archive', 'category', 'tag', 'author', 'search', 'index', 'archive-product', 'taxonomy-product_cat', 'taxonomy-product_tag', 'taxonomy-product_brand'] as $view) {
        expect(file_exists("$root/resources/views/$view.blade.php"))->toBeTrue("missing $view");
    }

    foreach (['home', 'archive', 'category', 'tag', 'search', 'index'] as $view) {
        expect(file_get_contents("$root/resources/views/$view.blade.php"))->toContain('partials.page-header', 'partials.post-grid');
    }
});

it('paginates with numbers, arrows and aria-current instead of Older and Newer links', function () use ($root) {
    $grid = file_get_contents("$root/resources/views/partials/post-grid.blade.php");
    $pagination = file_get_contents("$root/resources/views/partials/pagination.blade.php");
    $css = file_get_contents("$root/resources/css/shop-pages.css");

    expect($grid)->toContain('partials.pagination')->not->toContain('get_next_posts_link')->not->toContain('get_previous_posts_link')
        ->and($pagination)->toContain('paginate_links(', "'type' => 'list'", 'prev_text', 'next_text', 'pkde-pagination', 'aria-label')
        ->and($css)->toContain('.pkde-pagination .page-numbers.current', '.shop-archive__results .woocommerce-pagination');
});

it('adds its views directory to the view finder itself, so Sage lists the kit views as templates', function () use ($root) {
    $provider = file_get_contents("$root/src/KitServiceProvider.php");

    expect($provider)->toContain("callAfterResolving('view.finder'")->not->toContain("callAfterResolving('view',");
});
