<?php

// app/shape.php (WP-108 A): the theme.json fragment of the shape tokens; the CSS side is tested with the generator (tests/js) and proved on the template stack.
require_once dirname(__DIR__, 2) . '/app/shape.php';

use function App\shape_theme_json;

it('puts the three radius steps and the borders in theme.json custom settings and gives core buttons the full radius token', function () {
    $fragment = shape_theme_json(['preset' => 'round', 'radius' => ['sm' => '0.75rem', 'md' => '1.5rem', 'lg' => '3rem', 'full' => '9999px'], 'border' => ['thin' => '1px', 'thick' => '2px']]);

    expect($fragment['settings']['custom']['pkde']['radius']['md'])->toBe('1.5rem')
        ->and($fragment['settings']['custom']['pkde']['border']['thick'])->toBe('2px')
        ->and($fragment['styles']['elements']['button']['border']['radius'])->toBe('var(--pkde-radius-full)')
        ->and(array_keys($fragment['styles']))->toBe(['elements']); // nothing else is restyled
});

it('is loaded from the package and reads the generated shape file', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents("$root/composer.json"))->toContain('app/shape.php')
        ->and(file_get_contents("$root/app/shape.php"))->toContain("add_filter('wp_theme_json_data_theme'", 'resources/generated/shape.php');
});
