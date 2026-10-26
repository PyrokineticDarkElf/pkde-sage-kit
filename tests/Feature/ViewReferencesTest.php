<?php

/*
| Every view the kit names must exist in the kit. A renamed or moved view that something still includes by its old name would
| otherwise fail only when that page renders.
*/

function kitRoot(): string
{
    return dirname(__DIR__, 2);
}

/** @return list<string> */
function kitSourceFiles(): array
{
    $files = [];
    foreach (['app', 'resources/views', 'resources/blocks', 'src'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(kitRoot() . "/$dir", FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (preg_match('/\.php$/', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

it('only includes kit views that exist', function () {
    $missing = [];
    foreach (kitSourceFiles() as $file) {
        $source = file_get_contents($file);
        preg_match_all("/['\"]pkde-kit::([a-z0-9._-]+)['\"]/i", $source, $names);
        foreach ($names[1] as $name) {
            if (! is_file(kitRoot() . '/resources/views/' . str_replace('.', '/', $name) . '.blade.php')) {
                $missing[] = "$name (in " . substr($file, strlen(kitRoot()) + 1) . ')';
            }
        }
        preg_match_all('/<x-pkde-kit::([a-z0-9._-]+)/i', $source, $components);
        foreach ($components[1] as $name) {
            if (! is_file(kitRoot() . '/resources/views/components/' . str_replace('.', '/', $name) . '.blade.php')) {
                $missing[] = "component $name (in " . substr($file, strlen(kitRoot()) + 1) . ')';
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

it('keeps no un-namespaced partial or component reference that would resolve only through the fallback (a dynamic prefix such as partials.content-<type> is deliberate)', function () {
    $legacy = [];
    foreach (kitSourceFiles() as $file) {
        if (str_ends_with($file, 'View/Composers/Post.php')) {
            continue; // lists the old names on purpose
        }
        preg_match_all("/['\"](partials\.[a-z0-9._-]*[a-z0-9])['\"]|<x-(placeholder-image|alert)\b/i", file_get_contents($file), $m);
        foreach (array_filter(array_merge($m[1], $m[2])) as $name) {
            $legacy[] = "$name in " . substr($file, strlen(kitRoot()) + 1);
        }
    }

    expect($legacy)->toBe([]);
});

it('marks the Sale and New badge links nofollow, so crawlers do not follow every card into the same filtered views', function () {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/woocommerce/content-product.blade.php');

    preg_match_all("/<a href=\"\{\{ add_query_arg\('sage_view', '(?:sale|new)'[^>]*>/", $view, $links);

    expect($links[0])->toHaveCount(2);
    foreach ($links[0] as $link) {
        expect($link)->toContain('rel="nofollow"');
    }
});

it('keeps amber text and white-on-amber markers at 700 or darker, the lightest step that clears 4.5:1 on a white or light card', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__, 2) . '/resources/views/{,*/,*/*/}*.blade.php', GLOB_BRACE) as $file) {
        if (preg_match('/\b(?:text|bg)-amber-(?:[1-6]00|50)\b/', file_get_contents($file))) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

it('sets text on a brand or accent background with the foreground token, never a hard-coded white', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__, 2) . '/resources/views/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) as $file) {
        preg_match_all('/class="([^"]*)"/', file_get_contents($file), $attributes);

        foreach ($attributes[1] as $classes) {
            // A brand/accent background next to text-white, unless the same attribute already uses a -fg token (a dark button that
            // only turns brand on hover keeps its white text and sets the foreground token for the hover).
            if (preg_match('/\bbg-(?:brand|accent)-\d+\b/', $classes) && preg_match('/\btext-white\b/', $classes) && ! str_contains($classes, '-fg')) {
                $offenders[] = basename($file);
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('sizes page containers with max-w-content, the one token the header and footer use, never a fixed Tailwind width', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__, 2) . '/resources/views/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) as $file) {
        if (preg_match('/\bmax-w-(?:6xl|7xl)\b/', file_get_contents($file))) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

it('lets a site opt the Section block into the page measure with one variable, unlayered, falling back to the layout sizes', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');

    expect($css)->toContain('.wp-block-sage-section.is-layout-constrained')
        ->and($css)->toContain('var(--pkde-section-measure, var(--wp--style--global--content-size))')
        ->and($css)->toContain('var(--pkde-section-measure, var(--wp--style--global--wide-size))');
});

it('imports the shop CSS from kit.css and keeps the rating stars readable', function () {
    $kit = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');
    $shop = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');

    expect($kit)->toContain('@import "./shop-pages.css";')
        ->and($shop)->toContain('.star-rating > span::before')
        ->and($shop)->toContain('var(--color-amber-700)')
        ->and($shop)->toContain('.woocommerce ul.products li.product a.added_to_cart')
        ->and($shop)->toContain('body.woocommerce-cart');
});

it('has a social links partial that prints nothing without profiles and labels every link', function () {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/social-links.blade.php');

    expect($view)->toContain('App\\social_links()')
        ->and($view)->toContain('aria-label="{{ $link[\'label\'] }}"')
        ->and($view)->toContain('aria-hidden="true"');
});

it('gives every page container the shared gutter token, not a fixed px-6', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__, 2) . '/{resources/views,app,resources/blocks}/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE) as $file) {
        if (preg_match('/max-w-content[^"\']*\bpx-6\b|\bpx-6\b[^"\']*max-w-content/', file_get_contents($file))) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

it('gives the product description a reading width', function () {
    expect(file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css'))->toContain('.single-product .woocommerce-Tabs-panel:not(.woocommerce-Tabs-panel--reviews)');
});

it('gives the social links list a stable class a site can scope rules around, and styles the checkout shipping rows and heading', function () {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/social-links.blade.php');
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');

    expect($view)->toContain('<ul class="pkde-social-links ')
        ->and($css)->toContain('ul#shipping_method li:has(input:checked)')
        ->and($css)->not->toContain('.cart_totals ul#shipping_method')
        ->and($css)->toContain('#ship-to-different-address');
});

it('has no white stripe between Sections, a drawn zoom button, four-across related products and a drawn review star picker', function () {
    $kit = file_get_contents(dirname(__DIR__, 2) . '/resources/css/kit.css');
    $shop = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');

    expect($kit)->toContain('.prose-content > .wp-block-sage-section')
        ->and($shop)->toContain('.woocommerce-product-gallery__trigger::before')
        ->and($shop)->toContain('.woocommerce-product-gallery__trigger span')
        ->and($shop)->toContain('.woocommerce div.product .related ul.products')
        ->and($shop)->toContain('repeat(4, minmax(0, 1fr))')
        ->and($shop)->toContain('.woocommerce-Reviews p.stars a')
        ->and($shop)->toContain('.woocommerce-Reviews #review_form_wrapper')
        ->and($shop)->toContain('img.avatar');
});

it('draws the unselected review stars in neutral-500, a control needing 3:1, and keeps neutral-200 for the read-only rating track only', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/shop-pages.css');
    $picker = substr($css, strpos($css, '/* The star picker in the review form'), strpos($css, "/* The product's long description") - strpos($css, '/* The star picker in the review form'));

    expect($picker)->toContain('var(--color-neutral-500)')
        ->and($picker)->not->toContain('var(--color-neutral-200)')
        ->and($picker)->toContain('var(--color-amber-700)')
        ->and($css)->toContain('.star-rating::before {' . "\n" . '  background: var(--color-neutral-200);');
});

it('titles a product search "Search Results", not "Shop": the composer checks is_search() before is_archive()', function () {
    $source = file_get_contents(dirname(__DIR__, 2) . '/app/View/Composers/Post.php');
    $title = substr($source, strpos($source, 'public function title()'));

    expect(strpos($title, 'is_search()'))->toBeInt()
        ->and(strpos($title, 'is_search()'))->toBeLessThan(strpos($title, 'is_archive()'));
});

it('ships the optional Relevanssi live search: filters, results template, form, styles and settings script', function () {
    $root = dirname(__DIR__, 2);
    $search = file_get_contents("$root/app/search.php");

    expect($search)->toContain("'relevanssi_modify_wp_query'")
        ->and($search)->toContain("'relevanssi_live_search_results_template'")
        ->and($search)->toContain("'search_template_hierarchy'")
        ->and($search)->toContain("'relevanssi-live-search'")
        ->and(file_get_contents("$root/composer.json"))->toContain('"app/search.php"')
        ->and(file_get_contents("$root/resources/live-search/search-results.php"))->toContain("pkde-kit::partials.live-search-results")
        ->and(file_get_contents("$root/resources/views/partials/product-search.blade.php"))->toContain('data-rlvlive="true"')
        ->and(file_get_contents("$root/resources/views/partials/product-search.blade.php"))->toContain('name="post_type" value="product"')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./live-search.css";')
        ->and(file_exists("$root/bin/relevanssi-settings.php"))->toBeTrue();
});

it('has an opt-in header search whose icon and panel are wired by kit.js', function () {
    $root = dirname(__DIR__, 2);
    $partial = file_get_contents("$root/resources/views/partials/header-search.blade.php");

    expect($partial)->toContain('\App\header_search_enabled()')
        ->and($partial)->toContain('data-search-toggle')
        ->and($partial)->toContain('aria-controls="header-search-panel"')
        ->and(file_get_contents("$root/resources/js/kit.js"))->toContain('[data-search-toggle]');
});
