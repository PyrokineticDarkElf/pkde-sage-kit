<?php

namespace App;

/**
 * Reads a shop request's filter arguments before any product query runs (WP-96, C): which declared groups it names, which values exist, whether it is
 * within the caps, and what its one canonical URL is. Pure: WordPress only enters through the two callables, so it is tested without it.
 *
 * The answers:
 *  - 'notfound': a group has more than max_values_per_group values, more than max_active_groups groups are used, or every value given is unknown. The caller
 *    answers 404 with no product query and no filter UI.
 *  - 'redirect': the request is valid but not canonical (values unsorted or repeated, an unknown parameter or value, a bad price). The caller sends a 301 to
 *    'args', so identical choices share one URL and one cache entry.
 *  - 'ok': canonical as it stands.
 */
class ShopFilterRequest
{
    public const PREFIX = 'sage_filter_';

    /**
     * @param  array<string, mixed>  $get  the request's query arguments ($_GET, unslashed)
     * @param  array<string, string>  $taxonomyKeys  query argument name => taxonomy, in the declared group order
     * @param  string|null  $stockKey  the query argument of the stock toggle, when one is declared
     * @param  callable(string): string  $slugify  turns a value into a term slug (sanitize_title)
     * @param  callable(string, list<string>): list<string>  $existing  of those slugs, the ones that are terms of the taxonomy
     * @param  array{max_values_per_group: int, max_active_groups: int}  $caps
     * @return array{status: string, reason: string, selection: array<string, list<string>>, stock: bool, args: array<string, mixed>, filterArgs: array<string, mixed>}
     */
    public static function normalise(array $get, array $taxonomyKeys, ?string $stockKey, callable $slugify, callable $existing, array $caps): array
    {
        $given = [];      // filter argument => the values as given (strings, trimmed, empty ones left out), in request order
        $others = [];     // every other argument, in request order
        $price = [];
        $dropped = false;

        foreach ($get as $key => $value) {
            $key = (string) $key;

            if (str_starts_with($key, self::PREFIX)) {
                $values = [];
                foreach ((array) $value as $one) {
                    if (is_scalar($one) && trim((string) $one) !== '') {
                        $values[] = trim((string) $one);
                    } else {
                        $dropped = true; // an empty or nested value is noise: the canonical URL has none
                    }
                }
                $given[$key] = $values;
            } elseif ($key === 'min_price' || $key === 'max_price') {
                $price[$key] = $value;
            } else {
                $others[$key] = $value;
            }
        }

        $reason = static fn(string $status, string $why) => ['status' => $status, 'reason' => $why, 'selection' => [], 'stock' => false, 'args' => $others, 'filterArgs' => []];

        $active = array_filter($given, fn($values) => $values !== []);
        if (count($active) > $caps['max_active_groups']) {
            return $reason('notfound', 'more than ' . $caps['max_active_groups'] . ' filter groups');
        }
        foreach ($active as $key => $values) {
            if (count($values) > $caps['max_values_per_group']) {
                return $reason('notfound', 'more than ' . $caps['max_values_per_group'] . ' values for one group');
            }
        }

        $selection = [];
        $canonical = [];
        $stock = false;
        $valuesGiven = 0;
        $valuesKept = 0;

        foreach ($taxonomyKeys as $key => $taxonomy) {
            $values = $given[$key] ?? [];
            $valuesGiven += count($values);
            if ($values === []) {
                continue;
            }

            $slugs = array_values(array_unique(array_filter(array_map($slugify, $values), fn($slug) => $slug !== '')));
            $known = array_values(array_intersect($slugs, $existing($taxonomy, $slugs)));
            sort($known, SORT_STRING);

            if (count($known) !== count($values)) {
                $dropped = true;
            }
            if ($known !== []) {
                $selection[$taxonomy] = $known;
                $canonical[$key] = $known;
                $valuesKept += count($known);
            }
        }

        if ($stockKey !== null && isset($given[$stockKey]) && $given[$stockKey] !== []) {
            $valuesGiven += count($given[$stockKey]);
            if ($given[$stockKey] === ['1']) {
                $stock = true;
                $canonical[$stockKey] = ['1'];
                $valuesKept++;
            } else {
                $dropped = true;
            }
        }

        // A parameter that names no declared group is dropped (and counts as a value given that is unknown).
        foreach ($given as $key => $values) {
            if (! isset($taxonomyKeys[$key]) && $key !== $stockKey && $values !== []) {
                $valuesGiven += count($values);
                $dropped = true;
            }
        }

        if ($valuesGiven > 0 && $valuesKept === 0) {
            return $reason('notfound', 'every filter value is unknown');
        }

        $priceKept = [];
        foreach (['min_price', 'max_price'] as $key) {
            if (! array_key_exists($key, $price)) {
                continue;
            }
            $value = is_scalar($price[$key]) ? trim((string) $price[$key]) : '';
            if ($value !== '' && is_numeric($value) && (float) $value >= 0) {
                $priceKept[$key] = $value;
            } else {
                $dropped = true;
            }
        }

        $filterArgs = $canonical + $priceKept;

        // What the request carried, against the canonical form. The groups must come in the declared order with sorted, distinct values; the two price
        // arguments only have to be valid (the sidebar form sends them first, and that is not worth a redirect).
        $carried = [];
        foreach ($given as $key => $values) {
            if ($values !== []) {
                $carried[$key] = $values;
            }
        }
        $carriedPrice = [];
        foreach ($price as $key => $value) {
            $carriedPrice[$key] = is_scalar($value) ? trim((string) $value) : '';
        }
        ksort($carriedPrice);
        $keptPrice = $priceKept;
        ksort($keptPrice);
        // the stock toggle, like the price, may come anywhere
        $carriedStock = $stockKey !== null ? ($carried[$stockKey] ?? null) : null;
        $keptStock = $stockKey !== null ? ($canonical[$stockKey] ?? null) : null;
        if ($stockKey !== null) {
            unset($carried[$stockKey], $canonical[$stockKey]);
        }
        $same = ! $dropped && $carried === $canonical && $carriedStock === $keptStock && $carriedPrice === $keptPrice;
        if ($stockKey !== null && $keptStock !== null) {
            $canonical[$stockKey] = $keptStock;
        }

        return [
            'status' => $same ? 'ok' : 'redirect',
            'reason' => $same ? '' : 'not canonical',
            'selection' => $selection,
            'stock' => $stock,
            'args' => $others + $filterArgs,
            'filterArgs' => $filterArgs,
        ];
    }
}
