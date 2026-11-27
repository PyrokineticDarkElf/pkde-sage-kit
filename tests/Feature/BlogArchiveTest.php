<?php

/**
 * The blog archives and the shop archive views are the kit's: a theme without a file of that name gets these, and the blog pages
 * paginate with numbers in the shop's look. The tests read the files.
 */
$root = dirname(__DIR__, 2);

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

it('never lets a page in the search results resolve to the kit\'s page view', function () {
    $root = dirname(__DIR__, 2);

    expect(\App\post_card_views('page'))->toBe(['partials.card-page', 'pkde-kit::partials.content'])
        ->and(\App\post_card_views('post'))->toBe(['partials.card-post', 'partials.content-post', 'pkde-kit::partials.content'])
        ->and(\App\post_card_views('product'))->toBe(['partials.card-product', 'partials.content-product', 'pkde-kit::partials.content'])
        ->and(\App\post_card_views('single'))->not->toContain('partials.content-single')
        // The kit's view files the old lookup could hit by post-type name are all excluded.
        ->and(file_get_contents("$root/resources/views/partials/post-grid.blade.php"))->toContain('\\App\\post_card_views(get_post_type())')->not->toContain("'partials.content-' .");
});
