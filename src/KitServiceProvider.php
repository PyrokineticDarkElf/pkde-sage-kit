<?php

namespace Pkde\SageKit;

use App\View\Composers\App as AppComposer;
use App\View\Composers\Post as PostComposer;
use App\View\Composers\ProductArchive as ProductArchiveComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the kit's views and view composers with Acorn. Discovered automatically (composer.json extra.acorn.providers).
 *
 * The kit's views are the `pkde-kit` view namespace: `@include('pkde-kit::partials.breadcrumbs')`, `<x-pkde-kit::alert>`. A site overrides
 * one view by placing a file of the same path under resources/views/vendor/pkde-kit/ in its theme (Laravel's standard package
 * override; every other kit view still comes from the package). The kit's views directory is also added to the end of the view finder,
 * so a site's older un-namespaced includes (`partials.breadcrumbs`) keep resolving to the kit when the theme has no file of that name.
 */
class KitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $views = dirname(__DIR__) . '/resources/views';

        $this->loadViewsFrom($views, 'pkde-kit');
        $this->addViewLocation($views);

        // <x-pkde-kit::alert> resolves to the view pkde-kit::components.alert, so a vendor/pkde-kit override applies to components too.
        Blade::anonymousComponentNamespace('components', 'pkde-kit');

        foreach ([AppComposer::class, PostComposer::class, ProductArchiveComposer::class] as $composer) {
            View::composer($composer::views(), $composer);
        }
    }

    /**
     * The kit's views directory goes on the END of every view finder, so a theme without a file of that name falls back to the kit's.
     * Laravel binds `view.finder` as NOT shared, so there are two finders and both need it: Sage's own (it builds WordPress's template list from
     * its paths, so a kit `category` is a template at all) and the view factory's (so `exists('archive-product')` is true, the view is rendered
     * by name and its composer, App\View\Composers\ProductArchive, runs; on the factory's finder alone the shop answered 500 "Undefined variable
     * $shopPageContent", kit 2026.11.05.1).
     */
    protected function addViewLocation(string $views): void
    {
        $this->callAfterResolving('view.finder', fn($finder) => $finder->addLocation($views));
        $this->callAfterResolving('view', fn($view) => $view->getFinder()->addLocation($views));
    }
}
