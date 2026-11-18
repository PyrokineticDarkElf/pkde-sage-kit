<?php

/**
 * Faceted URLs are noindex, follow (WP-91, TPL-175): one rule (is_faceted_request), and the X-Robots-Tag header, the wp_robots tag and
 * Rank Math's robots meta all use it. The rule and Rank Math's answer run here; the three hooks need WordPress, so they are read from the file.
 */
use function App\faceted_rank_math_robots;

it('makes a faceted view noindex, follow in Rank Math and keeps its other directives', function () {
    $rankMath = ['index' => 'index', 'follow' => 'follow', 'max-snippet' => 'max-snippet:-1'];

    expect(faceted_rank_math_robots($rankMath, ['sage_filter_product_cat' => ['a']]))
        ->toBe(['index' => 'noindex', 'follow' => 'follow', 'max-snippet' => 'max-snippet:-1'])
        ->and(faceted_rank_math_robots(['index' => 'index', 'follow' => 'nofollow'], ['orderby' => 'price']))
        ->toBe(['index' => 'noindex', 'follow' => 'follow'])
        ->and(faceted_rank_math_robots($rankMath, ['paged' => '2']))->toBe($rankMath)
        ->and(faceted_rank_math_robots(['index' => 'noindex', 'follow' => 'follow'], []))->toBe(['index' => 'noindex', 'follow' => 'follow']);
});

it('sends the header, the wp_robots tag and Rank Math\'s robots, each from the rule', function () {
    $source = file_get_contents(dirname(__DIR__, 2) . '/app/woocommerce/filter-query.php');

    expect($source)->toContain("add_action('send_headers'")
        ->and($source)->toContain("header('X-Robots-Tag: noindex, follow')")
        ->and($source)->toContain("add_filter('wp_robots'")
        ->and($source)->toContain("add_filter('rank_math/frontend/robots'")
        ->and(substr_count($source, 'is_faceted_request($_GET)'))->toBe(2) // header, wp_robots
        ->and(substr_count($source, 'faceted_rank_math_robots('))->toBe(1);
});
