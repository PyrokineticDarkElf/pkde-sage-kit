<?php

// app/shape.php: the theme.json fragment of the shape tokens; the CSS side is tested with the generator (tests/js) and proved on the template stack.
require_once dirname(__DIR__, 2) . '/app/shape.php';

use function App\shape_theme_json;

it('puts the three radius steps and the borders in theme.json custom settings and gives core buttons the full radius token when it is not the default pill', function () {
    $fragment = shape_theme_json(['preset' => 'sharp', 'radius' => ['sm' => '0px', 'md' => '0px', 'lg' => '0px', 'full' => '0px'], 'border' => ['thin' => '1px', 'thick' => '2px']]);

    expect($fragment['settings']['custom']['pkde']['radius']['md'])->toBe('0px')
        ->and($fragment['settings']['custom']['pkde']['border']['thick'])->toBe('2px')
        ->and($fragment['styles']['elements']['button']['border']['radius'])->toBe('var(--pkde-radius-full)')
        ->and(array_keys($fragment['styles']))->toBe(['elements']); // nothing else is restyled
});

it('leaves the site\'s own button styles alone with the default shape', function () {
    $fragment = shape_theme_json(['preset' => 'soft', 'radius' => ['sm' => '0.5rem', 'md' => '1rem', 'lg' => '2rem', 'full' => '9999px'], 'border' => ['thin' => '1px', 'thick' => '2px']]);

    expect(array_keys($fragment))->toBe(['settings']);
});

it('is loaded from the package and reads the generated shape file', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents("$root/composer.json"))->toContain('app/shape.php')
        ->and(file_get_contents("$root/app/shape.php"))->toContain("add_filter('wp_theme_json_data_theme'", 'resources/generated/shape.php');
});
