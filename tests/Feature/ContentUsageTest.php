<?php

require_once dirname(__DIR__, 2) . '/bin/content-usage.php';

use function Pkde\SageKit\Tools\content_usage;
use function Pkde\SageKit\Tools\content_usage_table;

it('counts hidden blocks, the theme attribute, card styles and legacy classes, per post', function () {
    $rows = content_usage([
        ['ID' => 5, 'post_content' => '<!-- wp:sage/recent-products {"count":4,"theme":"brand"} /--><!-- wp:sage/recent-products /--><!-- wp:sage/hero {"theme":""} /-->'],
        ['ID' => 9, 'post_content' => '<!-- wp:sage/latest-posts {"cardStyle":"tinted","theme":"sage"} /--><div class="wp-block-group theme-sage pkde-card-bold"></div>'],
        ['ID' => 12, 'post_content' => '<!-- wp:sage/gallery /--><!-- wp:paragraph --><p>The color-theme-picker and a theme-less plugin</p><!-- /wp:paragraph -->'],
        ['ID' => 13, 'post_content' => ''],
    ]);

    expect($rows['block sage/recent-products'])->toBe(['posts' => [5], 'uses' => 2])
        ->and($rows['theme attribute on sage/recent-products']['posts'])->toBe([5])
        ->and($rows['theme attribute on sage/latest-posts']['posts'])->toBe([9])
        ->and($rows)->not->toHaveKey('theme attribute on sage/hero')
        ->and($rows['cardStyle tinted']['posts'])->toBe([9])
        ->and($rows['theme-* class'])->toBe(['posts' => [9], 'uses' => 1])
        ->and($rows['pkde-card-bold class']['posts'])->toBe([9])
        ->and($rows['pkde-card-tinted class'])->toBe(['posts' => [], 'uses' => 0])
        ->and($rows['block sage/gallery']['posts'])->toBe([12])
        ->and($rows['block sage/carousel'])->toBe(['posts' => [], 'uses' => 0]);
});

it('prints a table with a row for every hidden block, even at 0', function () {
    $table = content_usage_table(content_usage([]));

    expect($table)->toContain('block sage/entry-grid', 'block sage/hero-slider', 'cardStyle bold', 'theme-* class')->and(substr_count($table, "\n"))->toBeGreaterThan(10);
});
