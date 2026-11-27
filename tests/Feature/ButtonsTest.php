<?php

// app/buttons.php: the core Button block's styles and the Editor's restricted controls; the CSS side is tested with the generator (tests/js) and proved on the template stack.
require_once dirname(__DIR__, 2) . '/app/buttons.php';

use function App\button_block_styles;
use function App\button_class;
use function App\button_editor_restrictions;

it('gives the core Button block Solid, Outline and Text, Solid being the default', function () {
    $styles = button_block_styles([['name' => 'fill', 'label' => 'Fill', 'isDefault' => true], ['name' => 'outline', 'label' => 'Outline']]);

    expect(array_column($styles, 'label'))->toBe(['Solid', 'Outline', 'Text'])
        ->and(array_column($styles, 'name'))->toBe(['fill', 'outline', 'text']);
    // a registry without styles still gets all three, and nothing is added twice
    expect(array_column(button_block_styles([]), 'name'))->toBe(['fill', 'outline', 'text'])
        ->and(button_block_styles($styles))->toBe($styles);
});

it('names the class of every button variant and falls back to primary', function () {
    expect(button_class('secondary'))->toBe('pkde-btn pkde-btn--secondary')
        ->and(button_class('text'))->toBe('pkde-btn pkde-btn--text')
        ->and(button_class('nonsense'))->toBe('pkde-btn pkde-btn--primary');
});

it('takes the colour, size, border and spacing controls off the Button block for an Editor, leaving the style choice', function () {
    $core = button_editor_restrictions()['settings']['blocks']['core/button'];

    expect($core['color']['custom'])->toBeFalse()
        ->and($core['color']['background'])->toBeFalse()
        ->and($core['typography']['customFontSize'])->toBeFalse()
        ->and($core['border']['radius'])->toBeFalse()
        ->and($core['spacing']['padding'])->toBeFalse()
        ->and($core)->not->toHaveKey('styles'); // the style picker is not a setting: it stays
});

it('is loaded from the package and reads the generated buttons file', function () {
    $root = dirname(__DIR__, 2);

    expect(file_get_contents("$root/composer.json"))->toContain('app/buttons.php')
        ->and(file_get_contents("$root/app/buttons.php"))->toContain("add_filter('register_block_type_args'", 'resources/generated/buttons.php');
});

it('lets the hero and the CTA block choose a button style, defaulting to primary and secondary', function () {
    $root = dirname(__DIR__, 2);

    foreach (['hero', 'cta'] as $block) {
        $json = json_decode(file_get_contents("$root/resources/blocks/$block/block.json"), true);
        expect($json['attributes']['buttonStyle']['default'])->toBe('primary')
            ->and($json['attributes']['secondaryButtonStyle']['default'])->toBe('secondary')
            ->and($json['attributes']['buttonStyle']['enum'])->toBe(['primary', 'secondary', 'text']);
    }

    expect(\App\button_classes('text'))->toBe('pkde-btn pkde-btn--text')
        ->and(\App\button_classes('secondary'))->toBe('pkde-btn-alt')
        ->and(\App\button_classes())->toBe('pkde-btn')
        ->and(file_get_contents("$root/resources/blocks/cta/render.php"))->toContain('button_classes');
});
