<?php

namespace App;

use WP_Query;

/**
 * Builds lightweight faceted counts for the custom shop category/tag filters.
 * A category count reflects the active tag selection (and vice versa), so it
 * communicates the products a visitor can actually reach, not sitewide totals.
 */
class ShopFilters
{
    public static function taxonomies(): array
    {
        $taxonomies = [
            'product_cat' => __('Category', 'sage'),
            'product_tag' => __('Tags', 'sage'),
        ];

        if (taxonomy_exists('product_brand')) {
            $taxonomies['product_brand'] = __('Brand', 'sage');
        }

        foreach (wc_get_attribute_taxonomies() as $attribute) {
            $taxonomies[wc_attribute_taxonomy_name($attribute->attribute_name)] = $attribute->attribute_label;
        }

        return $taxonomies;
    }

    public static function queryKey(string $taxonomy): string
    {
        return 'sage_filter_' . $taxonomy;
    }

    public static function activeTerms(string $taxonomy): array
    {
        $terms = isset($_GET[self::queryKey($taxonomy)]) ? (array) wp_unslash($_GET[self::queryKey($taxonomy)]) : [];

        return array_values(array_filter(array_map('sanitize_title', $terms)));
    }

    public static function termCounts(string $taxonomy): array
    {
        $taxQuery = [];

        foreach (self::taxonomies() as $filterTaxonomy => $label) {
            if ($filterTaxonomy === $taxonomy) {
                continue;
            }

            $terms = self::activeTerms($filterTaxonomy);

            if ($terms) {
                $taxQuery[] = [
                    'taxonomy' => $filterTaxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN',
                ];
            }
        }

        $queried = get_queried_object();
        if ($queried instanceof \WP_Term && $queried->taxonomy !== $taxonomy) {
            $taxQuery[] = [
                'taxonomy' => $queried->taxonomy,
                'field' => 'term_id',
                'terms' => [$queried->term_id],
            ];
        }

        // hidden / search-only products are not counted (WP-Query does not leave them out by itself)
        $taxQuery[] = catalog_visibility_clause();

        if (count($taxQuery) > 1) {
            $taxQuery['relation'] = 'AND';
        }

        $products = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'tax_query' => $taxQuery,
        ]);

        if (! $products->posts) {
            return [];
        }

        $objectTerms = wp_get_object_terms($products->posts, $taxonomy, ['fields' => 'all_with_object_id']);
        if (is_wp_error($objectTerms)) {
            return [];
        }

        $counts = [];
        foreach ($objectTerms as $term) {
            $counts[$term->term_id][$term->object_id] = true;
        }

        return array_map('count', $counts);
    }

    /** The current request's query arguments, keys and values sanitised (the same reading the chips and the reset link use). @return array<string, mixed> */
    public static function currentQuery(): array
    {
        $query = [];
        foreach ($_GET as $key => $value) {
            $query[sanitize_key($key)] = is_array($value) ? array_map('sanitize_text_field', wp_unslash($value)) : sanitize_text_field(wp_unslash($value));
        }

        return $query;
    }

    /** The arguments that belong to the filter form: every sage_filter_* choice, the price range and the page number. */
    public static function isFilterArgument(string $key): bool
    {
        return str_starts_with($key, 'sage_filter_') || in_array($key, ['min_price', 'max_price', 'paged'], true);
    }

    /** Is any choice of the filter form applied (a sage_filter_* term or a price bound)? Pure. */
    public static function hasAppliedFilters(array $query): bool
    {
        foreach ($query as $key => $value) {
            if ((str_starts_with((string) $key, 'sage_filter_') || in_array($key, ['min_price', 'max_price'], true)) && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }

    /** The archive without the filter form's choices and the page number; every other argument (orderby, a search, a sage_view) stays. Pure. */
    public static function resetUrl(string $baseUrl, array $query): string
    {
        $args = array_filter($query, fn($value, $key) => ! self::isFilterArgument((string) $key) && $value !== '' && $value !== [], ARRAY_FILTER_USE_BOTH);

        return $args ? $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query($args) : $baseUrl;
    }

    /**
     * The filters currently applied as removable chips: each one links to this same archive with just that choice taken out.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function activeChips(string $baseUrl): array
    {
        $query = self::currentQuery();
        unset($query['paged']);

        $urlWithout = function (string|array $keys, ?string $slug = null) use ($query, $baseUrl) {
            $args = $query;
            foreach ((array) $keys as $key) {
                if ($slug !== null && is_array($args[$key] ?? null)) {
                    $args[$key] = array_values(array_diff($args[$key], [$slug]));
                } else {
                    unset($args[$key]);
                }
            }
            $args = array_filter($args, fn($v) => $v !== [] && $v !== '');

            return $args ? $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query($args) : $baseUrl;
        };

        $chips = [];

        foreach (['sale' => __('Sale', 'sage'), 'new' => __('New', 'sage')] as $view => $label) {
            if (($query['sage_view'] ?? '') === $view) {
                $chips[] = ['label' => $label, 'url' => $urlWithout('sage_view')];
            }
        }

        foreach (self::taxonomies() as $taxonomy => $heading) {
            foreach (self::activeTerms($taxonomy) as $slug) {
                $term = get_term_by('slug', $slug, $taxonomy);
                $chips[] = ['label' => $heading . ': ' . ($term ? $term->name : $slug), 'url' => $urlWithout(self::queryKey($taxonomy), $slug)];
            }
        }

        if (($query['min_price'] ?? '') !== '' || ($query['max_price'] ?? '') !== '') {
            $chips[] = [
                'label' => __('Price', 'sage') . ': ' . trim(($query['min_price'] ?? '') !== '' ? wc_price((float) $query['min_price']) : '', ' ') . _x(' – ', 'separator between the minimum and the maximum price in an active filter chip', 'sage') . (($query['max_price'] ?? '') !== '' ? wc_price((float) $query['max_price']) : __('any', 'sage')),
                'url' => $urlWithout(['min_price', 'max_price']),
            ];
        }

        return $chips;
    }
}
