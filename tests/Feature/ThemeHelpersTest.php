<?php

// helpers.php defines its functions and returns early outside WordPress, so it loads here without WordPress.
require_once dirname(__DIR__, 2) . '/app/helpers.php';

use function App\container_class;
use function App\iframe_titles;
use function App\logo_sizes;
use function App\next_hero_slide_number;
use function App\png_output_formats;
use function App\webp_quality;

it('numbers the hero slides of a request consecutively (the counter survives between calls)', function () {
    $first = next_hero_slide_number();

    expect(next_hero_slide_number())->toBe($first + 1)
        ->and(next_hero_slide_number())->toBe($first + 2);
});

it('sizes the header logo to the width it is drawn at', function () {
    // 240x64 drawn 32px high below sm and 40px high from sm: 120px and 150px wide
    expect(logo_sizes(240, 64))->toBe('(min-width: 640px) 150px, 120px')
        ->and(logo_sizes(240, 64, 40, 48))->toBe('(min-width: 640px) 180px, 150px')
        ->and(logo_sizes(100, 100))->toBe('(min-width: 640px) 40px, 32px')
        ->and(logo_sizes(0, 64))->toBe('')
        ->and(logo_sizes(240, 0))->toBe('');
});

it('saves PNG sub-sizes as WebP and keeps every other format mapping', function () {
    expect(png_output_formats([]))->toBe(['image/png' => 'image/webp'])
        ->and(png_output_formats(['image/bmp' => 'image/jpeg']))->toBe(['image/png' => 'image/webp', 'image/bmp' => 'image/jpeg'])
        ->and(png_output_formats(['image/png' => 'image/png']))->toBe(['image/png' => 'image/webp']);
});

it('sets the WebP quality and leaves other mime types alone', function () {
    expect(webp_quality(82, 'image/webp'))->toBe(80)
        ->and(webp_quality(82, 'image/jpeg'))->toBe(82)
        ->and(webp_quality(90, 'image/png'))->toBe(90);
});

it('marks shop filter, price and sale/new views as faceted requests', function () {
    expect(\App\is_faceted_request(['sage_filter_product_cat' => ['a']]))->toBeTrue()
        ->and(\App\is_faceted_request(['sage_view' => 'sale']))->toBeTrue()
        ->and(\App\is_faceted_request(['min_price' => '5']))->toBeTrue()
        ->and(\App\is_faceted_request(['max_price' => '50']))->toBeTrue()
        ->and(\App\is_faceted_request(['s' => 'clock', 'post_type' => 'product']))->toBeFalse()
        ->and(\App\is_faceted_request(['paged' => '2', 'orderby' => 'price']))->toBeFalse()
        ->and(\App\is_faceted_request([]))->toBeFalse();
});

it('gives an embed iframe a title only when it has none', function () {
    $map = '<iframe src="https://www.google.com/maps/embed?pb=1" width="600" loading="lazy"></iframe>';
    expect(iframe_titles($map, 'Map of "Home" & away'))
        ->toBe('<iframe title="Map of &quot;Home&quot; &amp; away" src="https://www.google.com/maps/embed?pb=1" width="600" loading="lazy"></iframe>');

    $titled = '<iframe src="x" title="Our park"></iframe>';
    expect(iframe_titles($titled, 'Map'))->toBe($titled);
    expect(iframe_titles("<IFRAME SRC='x' TITLE=''></IFRAME>", 'Map'))->toBe('<iframe title="Map" SRC=\'x\'></IFRAME>');
    expect(iframe_titles('<p>No frame</p>', 'Map'))->toBe('<p>No frame</p>')
        ->and(iframe_titles('<iframe src="a"></iframe><iframe src="b" title="B"></iframe>', 'Map'))
        ->toBe('<iframe title="Map" src="a"></iframe><iframe src="b" title="B"></iframe>');
});

it('returns the default container classes without WordPress and when no filter changes them', function () {
    expect(container_class())->toBe('mx-auto w-full max-w-content px-6')
        ->and(container_class('mx-auto max-w-7xl px-6', 'partials.page-header'))->toBe('mx-auto max-w-7xl px-6');
});

it('lets a site replace a container class through the pkde_kit/container_class filter, per place, and ignores a bad answer', function () {
    $GLOBALS['pkde_test_filters'] = [];
    if (! function_exists('apply_filters')) {
        function apply_filters(string $hook, $value, ...$args)
        {
            foreach ($GLOBALS['pkde_test_filters'][$hook] ?? [] as $callback) {
                $value = $callback($value, ...$args);
            }

            return $value;
        }
    }

    $GLOBALS['pkde_test_filters']['pkde_kit/container_class'][] = fn($class, $context) => $context === 'sage/hero' ? 'site-container' : $class;
    expect(container_class('mx-auto w-full max-w-content px-6', 'sage/hero'))->toBe('site-container')
        ->and(container_class('mx-auto w-full max-w-content px-6', 'sage/hero-slide'))->toBe('mx-auto w-full max-w-content px-6');

    $GLOBALS['pkde_test_filters']['pkde_kit/container_class'] = [fn() => ['not a string']];
    expect(container_class('default-class', 'sage/hero'))->toBe('default-class');
    $GLOBALS['pkde_test_filters']['pkde_kit/container_class'] = [fn() => ''];
    expect(container_class('default-class', 'sage/hero'))->toBe('default-class');
    $GLOBALS['pkde_test_filters'] = [];
});

it('prints every container through container_class: no hard-coded content container is left in a block render or kit view', function () {
    $root = dirname(__DIR__, 2);
    $offenders = [];
    foreach (['resources/blocks/hero/render.php', 'resources/blocks/hero-slide/render.php', 'resources/views/partials/page-header.blade.php', 'resources/views/partials/post-grid.blade.php', 'resources/views/partials/content-single.blade.php', 'resources/views/partials/shop-archive.blade.php'] as $file) {
        if (preg_match('/class="mx-auto[^"]*"/', file_get_contents("$root/$file"))) {
            $offenders[] = $file;
        }
    }

    expect($offenders)->toBe([]);
});

if (! function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return $GLOBALS['pkde_test_options'][$name] ?? $default;
    }
}

it('lists the social profiles that have a valid URL, in a fixed order', function () {
    $GLOBALS['pkde_test_options'] = [
        'client_youtube' => 'https://www.youtube.com/@example',
        'client_facebook' => 'https://www.facebook.com/example',
        'client_instagram' => 'not a url',
        'client_x' => '',
    ];

    expect(array_keys(\App\social_links()))->toBe(['facebook', 'youtube'])
        ->and(\App\social_links()['facebook'])->toBe(['label' => 'Facebook', 'url' => 'https://www.facebook.com/example']);

    $GLOBALS['pkde_test_options'] = [];
    expect(\App\social_links())->toBe([]);
});
