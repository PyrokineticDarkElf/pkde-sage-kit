<?php

/**
 * Shop filter requests are validated before any product query runs (C): caps, unknown groups and values, one canonical URL. Pure; WordPress is
 * replaced by a slug list.
 */
use App\ShopFilterRequest;

function pkdeFilterRun(array $get, array $caps = ['max_values_per_group' => 3, 'max_active_groups' => 2], bool $stock = true): array
{
    $terms = ['product_cat' => ['chairs', 'tables', 'lamps', 'rugs'], 'pa_colour' => ['blue', 'red', 'green'], 'product_brand' => ['acme']];

    return ShopFilterRequest::normalise(
        $get,
        ['sage_filter_product_cat' => 'product_cat', 'sage_filter_pa_colour' => 'pa_colour', 'sage_filter_product_brand' => 'product_brand'],
        $stock ? 'sage_filter_stock' : null,
        fn(string $v) => strtolower(preg_replace('/[^A-Za-z0-9_-]/', '', $v)),
        fn(string $taxonomy, array $slugs) => array_values(array_intersect($slugs, $terms[$taxonomy] ?? [])),
        $caps,
    );
}

it('leaves a canonical request alone', function () {
    $r = pkdeFilterRun(['orderby' => 'price', 'sage_filter_product_cat' => ['chairs', 'tables'], 'sage_filter_pa_colour' => ['red'], 'min_price' => '10', 'max_price' => '99']);

    expect($r['status'])->toBe('ok')
        ->and($r['selection'])->toBe(['product_cat' => ['chairs', 'tables'], 'pa_colour' => ['red']]);
});

it('does not send the sidebar form to a redirect for its price fields coming first', function () {
    expect(pkdeFilterRun(['min_price' => '5', 'sage_filter_product_cat' => ['chairs']])['status'])->toBe('ok');
});

it('sorts the values, removes repeats and puts the groups in the declared order, with a 301 to the one canonical URL', function () {
    $r = pkdeFilterRun(['sage_filter_pa_colour' => ['red', 'blue'], 'sage_filter_product_cat' => ['tables', 'chairs', 'tables'], 'page' => '2']);

    expect($r['status'])->toBe('redirect')
        ->and(array_keys($r['args']))->toBe(['page', 'sage_filter_product_cat', 'sage_filter_pa_colour'])
        ->and($r['args']['sage_filter_product_cat'])->toBe(['chairs', 'tables'])
        ->and($r['args']['sage_filter_pa_colour'])->toBe(['blue', 'red']);

    // the canonical form of that URL is itself canonical: no redirect loop
    expect(pkdeFilterRun($r['args'])['status'])->toBe('ok');
});

it('drops unknown values, unknown groups and a bad price, and redirects, while a valid choice remains', function () {
    $r = pkdeFilterRun(['sage_filter_product_cat' => ['chairs', 'nonsense'], 'sage_filter_pa_size' => ['xl'], 'min_price' => 'abc', 'max_price' => '50']);

    expect($r['status'])->toBe('redirect')
        ->and($r['args'])->toBe(['sage_filter_product_cat' => ['chairs'], 'max_price' => '50']);
});

it('answers 404 when every value is unknown, with nothing left to show', function () {
    expect(pkdeFilterRun(['sage_filter_product_cat' => ['nonsense']])['status'])->toBe('notfound')
        ->and(pkdeFilterRun(['sage_filter_pa_size' => ['xl'], 'sage_filter_product_cat' => ['gone']])['status'])->toBe('notfound');
});

it('answers 404 over the caps, before looking at any term', function () {
    $lookups = 0;
    $over = ShopFilterRequest::normalise(
        ['sage_filter_product_cat' => ['a', 'b', 'c', 'd']],
        ['sage_filter_product_cat' => 'product_cat'],
        null,
        'strtolower',
        function () use (&$lookups) {
            $lookups++;
            return [];
        },
        ['max_values_per_group' => 3, 'max_active_groups' => 2],
    );
    expect($over['status'])->toBe('notfound')->and($over['reason'])->toContain('values for one group')->and($lookups)->toBe(0);

    $groups = pkdeFilterRun(['sage_filter_product_cat' => ['chairs'], 'sage_filter_pa_colour' => ['red'], 'sage_filter_product_brand' => ['acme']]);
    expect($groups['status'])->toBe('notfound')->and($groups['reason'])->toContain('filter groups');

    // exactly at the caps is fine
    expect(pkdeFilterRun(['sage_filter_product_cat' => ['chairs', 'lamps', 'tables'], 'sage_filter_pa_colour' => ['red']])['status'])->toBe('ok');
});

it('ignores empty values and counts the repeats a flood sends', function () {
    expect(pkdeFilterRun(['sage_filter_product_cat' => ['', 'chairs']])['status'])->toBe('redirect')
        ->and(pkdeFilterRun(['sage_filter_product_cat' => array_fill(0, 40, 'chairs')])['status'])->toBe('notfound')
        ->and(pkdeFilterRun(['sage_filter_product_cat' => [['x'], 'chairs']])['selection'])->toBe(['product_cat' => ['chairs']]);
});

it('reads the stock toggle only as 1', function () {
    expect(pkdeFilterRun(['sage_filter_stock' => '1'])['stock'])->toBeTrue()
        ->and(pkdeFilterRun(['sage_filter_stock' => '1'])['status'])->toBe('ok')
        ->and(pkdeFilterRun(['sage_filter_stock' => '1', 'sage_filter_product_cat' => ['chairs']])['status'])->toBe('ok')
        ->and(pkdeFilterRun(['sage_filter_stock' => '2'])['status'])->toBe('notfound')
        ->and(pkdeFilterRun(['sage_filter_stock' => '1'], stock: false)['status'])->toBe('notfound');
});

it('a request with no filter arguments is untouched', function () {
    $r = pkdeFilterRun(['orderby' => 'price', 'paged' => '3']);

    expect($r['status'])->toBe('ok')->and($r['selection'])->toBe([])->and($r['args'])->toBe(['orderby' => 'price', 'paged' => '3']);
});
