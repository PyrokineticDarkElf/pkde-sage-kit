<?php

// app/block-assets.php defines a pure function and returns early outside WordPress, so it loads here without WordPress.
require_once dirname(__DIR__, 2) . '/app/block-assets.php';

use function App\block_variation_styles_in_footer;

it('moves core\'s early variation-styles enqueue to the footer only for a classic theme loading block assets on demand', function () {
    expect(block_variation_styles_in_footer(false, true))->toBeTrue()
        ->and(block_variation_styles_in_footer(true, true))->toBeFalse()
        ->and(block_variation_styles_in_footer(false, false))->toBeFalse()
        ->and(block_variation_styles_in_footer(true, false))->toBeFalse();
});

it('does it after global-styles exists, and never turns separate block assets off again (TCS-87, EL-13)', function () {
    $root = dirname(__DIR__, 2);
    $source = file_get_contents("$root/app/block-assets.php");

    expect($source)->toContain("remove_action('wp_enqueue_scripts', 'wp_enqueue_block_style_variation_styles', 1)")
        ->and($source)->toContain("add_action('wp_footer', 'wp_enqueue_block_style_variation_styles', 2)")
        ->and(file_get_contents("$root/composer.json"))->toContain('app/block-assets.php');

    foreach (['app/setup.php', 'app/filters.php', 'app/blocks.php', 'app/block-assets.php'] as $file) {
        expect(file_get_contents("$root/$file"))->not->toMatch("/add_filter\\(\\s*'should_load_separate_core_block_assets'/");
    }
});
