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

it('adds its views directory to the view finder itself (Sage lists the kit views as templates) and to the view factory\'s', function () use ($root) {
    $provider = file_get_contents("$root/src/KitServiceProvider.php");

    expect($provider)->toContain("callAfterResolving('view.finder'", "callAfterResolving('view',");
});

/**
 * A theme WITHOUT archive-product.blade.php must still get the kit's, rendered by NAME (so the ProductArchive composer, bound to that name, runs).
 * Laravel binds `view.finder` as a non-shared service, so Sage's finder and the view factory's finder are two objects: both must hold the kit's
 * directory. Kit 2026.11.05.1 put it on Sage's only, and /shop/ answered 500 "Undefined variable $shopPageContent" (tcs, 2026-11-05).
 */
it('puts the kit views on every view finder, so a theme without archive-product still renders the kit view by name', function () use ($root) {
    $theme = realpath(sys_get_temp_dir()) . '/pkde-theme-' . bin2hex(random_bytes(4));
    mkdir($theme);
    file_put_contents("$theme/category.blade.php", 'theme');

    $app = new Illuminate\Container\Container();
    $app->bind('view.finder', fn() => new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem(), [$theme]));
    $app->singleton('view', fn($app) => new class ($app->make('view.finder')) {
        public function __construct(private $finder) {}

        public function getFinder()
        {
            return $this->finder;
        }
    });

    $provider = new Pkde\SageKit\KitServiceProvider($app);
    (new ReflectionMethod($provider, 'addViewLocation'))->invoke($provider, "$root/resources/views");

    $sageFinder = $app->make('view.finder');
    $factoryFinder = $app->make('view')->getFinder();

    expect($sageFinder)->not->toBe($factoryFinder);

    foreach ([$sageFinder, $factoryFinder] as $finder) {
        expect($finder->find('archive-product'))->toBe("$root/resources/views/archive-product.blade.php")
            ->and($finder->find('home'))->toBe("$root/resources/views/home.blade.php")
            ->and($finder->find('category'))->toBe("$theme/category.blade.php");
    }

    unlink("$theme/category.blade.php");
    rmdir($theme);
});

it('pads the Shop page notices row only under the page header or when there is a notice (no empty strip with the header off)', function () use ($root) {
    $page = file_get_contents("$root/resources/views/partials/shop-page.blade.php");

    expect($page)->toContain('wc_notice_count()', '@if ($showHeader || $hasNotices)', '@else')
        ->and(substr_count($page, 'pt-8'))->toBe(1)
        ->and($page)->toMatch('/@else\s+@include\(.pkde-kit::partials\.woocommerce-notices.\)/');
});
