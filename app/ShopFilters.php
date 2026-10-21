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

    /**
     * The filters currently applied as removable chips: each one links to this same archive with just that choice taken out.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function activeChips(string $baseUrl): array
    {
        $query = [];
        foreach ($_GET as $key => $value) {
            $query[sanitize_key($key)] = is_array($value) ? array_map('sanitize_text_field', wp_unslash($value)) : sanitize_text_field(wp_unslash($value));
        }
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
                'label' => __('Price', 'sage') . ': ' . trim(($query['min_price'] ?? '') !== '' ? wc_price((float) $query['min_price']) : '', ' ') . ' – ' . (($query['max_price'] ?? '') !== '' ? wc_price((float) $query['max_price']) : __('any', 'sage')),
                'url' => $urlWithout(['min_price', 'max_price']),
            ];
        }

        return $chips;
    }
}
