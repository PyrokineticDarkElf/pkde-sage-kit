<?php

/**
 * The declaration of shop filters (WP-96, A): each entry resolved in full, a source that is not usable left out, and the exact clause flag the counts rely on.
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
