<?php

/**
 * The declaration of shop filters (A): each entry resolved in full, a source that is not usable left out, and the exact clause flag the counts rely on.
 */
use App\ShopFilterCounts;
use App\ShopFilters;

if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

function pkdeGroup(array $raw): ?array
{
    return ShopFilters::normaliseGroup($raw, fn(string $s) => in_array($s, ['product_cat', 'product_brand', 'pa_colour'], true), fn(string $s) => 'Label for ' . $s);
}

it('resolves a taxonomy group with its defaults', function () {
    expect(pkdeGroup(['source' => 'pa_colour']))->toBe(['source' => 'pa_colour', 'type' => 'taxonomy', 'taxonomy' => 'pa_colour', 'label' => 'Label for pa_colour', 'hierarchical' => false, 'order' => 'name', 'limit' => null]);
});

it('takes every key a site may set', function () {
    expect(pkdeGroup(['source' => 'product_cat', 'label' => 'Room', 'hierarchical' => true, 'order' => 'menu_order', 'limit' => 8]))
        ->toBe(['source' => 'product_cat', 'type' => 'taxonomy', 'taxonomy' => 'product_cat', 'label' => 'Room', 'hierarchical' => true, 'order' => 'menu_order', 'limit' => 8]);
});

it('falls back for an unknown order and a limit that is not a positive number', function () {
    $g = pkdeGroup(['source' => 'product_brand', 'order' => 'random', 'limit' => 0]);

    expect($g['order'])->toBe('name')->and($g['limit'])->toBeNull();
});

it('knows price as a range and stock as a toggle, with a label of their own', function () {
    expect(pkdeGroup(['source' => 'price', 'type' => 'range']))->toMatchArray(['type' => 'range', 'taxonomy' => null, 'label' => 'Price'])
        ->and(pkdeGroup(['source' => 'stock', 'type' => 'toggle', 'label' => 'Ready to ship']))->toMatchArray(['type' => 'toggle', 'label' => 'Ready to ship']);
});

it('skips a source that is not a product taxonomy, or has no name, instead of failing', function () {
    expect(pkdeGroup(['source' => 'category']))->toBeNull()
        ->and(pkdeGroup(['source' => 'pa_doesnotexist']))->toBeNull()
        ->and(pkdeGroup(['label' => 'No source']))->toBeNull()
        ->and(pkdeGroup([]))->toBeNull();
});

it('takes only the flagged filter clauses out of a tax query, whatever else the query holds', function () {
    $visibility = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude-from-catalog'], 'operator' => 'NOT IN'];
    $ours = ['taxonomy' => 'pa_colour', 'field' => 'slug', 'terms' => ['red'], 'operator' => 'IN', 'pkde_filter' => true];
    $stock = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['outofstock'], 'operator' => 'NOT IN', 'pkde_filter' => true];

    expect(ShopFilterCounts::withoutOurClauses([$visibility, $ours, $stock, 'relation' => 'AND']))->toBe([0 => $visibility])
        ->and(ShopFilterCounts::withoutOurClauses([$visibility, 'relation' => 'AND']))->toBe([$visibility])
        ->and(ShopFilterCounts::withoutOurClauses([]))->toBe([]);
});

/**: on a search the counts' base query must be answered by Relevanssi like the main one, or every count is 0 and a search with no filter shows no groups. */
it('flags the counts base query for Relevanssi on a search, and only then', function () {
    expect(App\ShopFilterCounts::searchBaseArgs(['s' => 'clock', 'post_type' => ['product']], true))->toBe(['s' => 'clock', 'post_type' => ['product'], 'relevanssi' => true])
        ->and(App\ShopFilterCounts::searchBaseArgs(['s' => 'clock'], false))->toBe(['s' => 'clock'])      // no Relevanssi: WordPress's own search, unchanged
        ->and(App\ShopFilterCounts::searchBaseArgs(['post_type' => 'product'], true))->toBe(['post_type' => 'product']) // not a search
        ->and(App\ShopFilterCounts::searchBaseArgs(['s' => ''], true))->toBe(['s' => '']);

    $source = file_get_contents(dirname(__DIR__, 2) . '/app/ShopFilterCounts.php');
    expect($source)->toContain('searchBaseArgs($vars, function_exists(\'relevanssi_do_query\'))', 'pkde_kit/filter_counts_base_args');
});

/** (a site, kit .7): the counts were still LOW beside the grid on a big search, and a bump kept serving the old cached counts. */
it('lifts Relevanssi\'s hit limit for the counts base set, so every hit is counted before the choices narrow it', function () {
    expect(App\ShopFilterCounts::offFilter())->toBe('off');

    $source = file_get_contents(dirname(__DIR__, 2) . '/app/ShopFilterCounts.php');
    expect($source)->toContain("add_filter('pre_option_relevanssi_throttle', [self::class, 'offFilter'])", "remove_filter('pre_option_relevanssi_throttle', [self::class, 'offFilter'])")
        ->and($source)->toContain('relevanssi_throttle');
});

it('puts the counts logic version and the kit version in the cache key, so an upgrade drops the old counts', function () {
    expect(App\ShopFilterCounts::codeVersion())->toStartWith(App\ShopFilterCounts::SCHEMA . '|');

    $source = file_get_contents(dirname(__DIR__, 2) . '/app/ShopFilterCounts.php');
    expect($source)->toContain('self::version(), self::codeVersion(), $query');
});
