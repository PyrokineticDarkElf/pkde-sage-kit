<?php

namespace App;

use WP_Query;

/**
 * The count beside each filter option (WP-96, B and C).
 *
 * The invariant: the count beside an option equals the number of products the grid shows after choosing that option ON ITS OWN in its group (replacing the
 * group's own choice, as a visitor does by unticking the others), on top of every other applied choice. Within a group the choices are OR, between groups AND.
 *
 * How, in a bounded number of queries:
 *  1. The BASE set: the main query's own arguments with only our filter choices taken out (archive, search, price, sale/new/featured views, catalogue
 *     visibility, stock hiding, published): one ID query, so the counts and the grid cannot disagree about what a "product" is. WooCommerce's price clause
 *     is main-query only, so it is switched on for this query with its own filter (WooCommerce 9.9 and later).
 *  2. One query per ACTIVE group: its chosen terms (and their children, as the grid's tax query includes them) narrowed to the base set.
 *  3. A group's counts come from the products in the base set that match every OTHER active group: one query per active group, plus one shared by all
 *     the groups with no choice. A hierarchical taxonomy counts a product once for every ancestor of its terms.
 * On a cache miss: at most 2 x (active groups) + 2 product-side queries, plus one more per 5000 products in the set, whatever the number of groups.
 * On a cache hit: none. The cache key holds the canonical state, the archive and a catalogue version bumped when a product or a product term changes.
 */
class ShopFilterCounts
{
    private const CHUNK = 5000;

    /** @var array<string, array<int, int>>|null */
    private static ?array $result = null;

    /** @var array<string, int> how many database queries the last computation ran, by purpose (for the measurement and the tests) */
    public static array $queries = [];

    public static function reset(): void
    {
        self::$result = null;
        self::$queries = [];
    }

    /** @return array<string, array<int, int>> taxonomy => term_id => count, for every taxonomy group */
    public static function all(): array
    {
        if (self::$result !== null) {
            return self::$result;
        }

        $key = self::cacheKey();
        $cached = get_transient($key);
        if (is_array($cached)) {
            self::$queries = ['cache' => 1];

            return self::$result = $cached;
        }

        self::$queries = [];
        $counts = self::compute();
        $ttl = ShopFilters::config()['counts_cache_ttl'];
        if ($ttl > 0) {
            set_transient($key, $counts, $ttl);
        }

        return self::$result = $counts;
    }

    /** Bumped when the catalogue changes, so every cached count is stale at once. */
    public static function version(): int
    {
        return (int) get_option('pkde_shop_filter_version', 1);
    }

    public static function bumpVersion(): void
    {
        update_option('pkde_shop_filter_version', self::version() + 1, false);
        self::$result = null;
    }

    private static function cacheKey(): string
    {
        global $wp_query;

        $query = is_object($wp_query) ? (array) $wp_query->query : [];
        unset($query['paged'], $query['page'], $query['orderby'], $query['order']);
        ksort($query);

        $state = ShopFilters::request();
        $price = [];
        foreach (['min_price', 'max_price'] as $key) {
            if (isset($state['filterArgs'][$key])) {
                $price[$key] = $state['filterArgs'][$key];
            }
        }
        $groups = array_map(fn($g) => $g['source'] . ($g['hierarchical'] ? '+h' : ''), ShopFilters::groups());

        return 'pkde_sfc_' . md5(wp_json_encode([self::version(), $query, $state['selection'], $state['stock'], $price, $_GET['sage_view'] ?? '', $_GET['featured'] ?? '', $groups, get_option('woocommerce_hide_out_of_stock_items')]));
    }

    /** @return array<string, array<int, int>> */
    private static function compute(): array
    {
        $taxonomyGroups = array_values(array_filter(ShopFilters::groups(), fn($g) => $g['type'] === 'taxonomy'));
        $base = self::baseIds();
        $empty = array_fill_keys(array_column($taxonomyGroups, 'source'), []);
        if ($base === []) {
            return $empty;
        }

        // The products matching each ACTIVE group, inside the base set.
        $state = ShopFilters::request();
        $sets = [];
        foreach ($taxonomyGroups as $group) {
            $chosen = $state['selection'][$group['source']] ?? [];
            if ($chosen !== []) {
                $sets[$group['source']] = self::idsWithTerms($group['source'], $chosen, $base);
            }
        }
        if ($state['stock'] && ShopFilters::hasStockToggle()) {
            $sets['stock'] = self::inStock($base);
        }

        $intersect = function (string $except) use ($sets, $base): array {
            $scope = null;
            foreach ($sets as $source => $ids) {
                if ($source !== $except) {
                    $scope = $scope === null ? $ids : array_intersect_key($scope, $ids);
                }
            }

            return $scope ?? array_fill_keys($base, true);
        };

        $counts = $empty;
        $withoutChoice = array_values(array_filter($taxonomyGroups, fn($g) => ! isset($sets[$g['source']])));
        if ($withoutChoice) {
            $counts = array_replace($counts, self::countTerms($withoutChoice, array_keys($intersect(''))));
        }
        foreach ($taxonomyGroups as $group) {
            if (isset($sets[$group['source']])) {
                $counts = array_replace($counts, self::countTerms([$group], array_keys($intersect($group['source']))));
            }
        }

        return $counts;
    }

    /** The IDs of the products the grid would list for this archive with none of our choices applied. @return list<int> */
    private static function baseIds(): array
    {
        global $wp_query;

        if (! is_object($wp_query)) {
            return [];
        }

        $vars = $wp_query->query_vars;
        $ours = array_filter((array) ($vars['tax_query'] ?? []), fn($clause) => is_array($clause) && ! empty($clause['pkde_filter']));

        // WordPress copies a single IN clause into the `taxonomy` and `term` query vars (it makes that term the queried object). When that pair is ours it goes too,
        // or the "base" would still be narrowed by the choice we are counting around. A pair the request itself carried (a taxonomy archive) stays.
        $raw = (array) $wp_query->query;
        foreach ($ours as $clause) {
            if (! isset($raw['taxonomy']) && ! isset($raw['term']) && ($vars['taxonomy'] ?? '') === ($clause['taxonomy'] ?? null) && ($vars['term'] ?? '') === (string) (((array) ($clause['terms'] ?? []))[0] ?? '')) {
                unset($vars['taxonomy'], $vars['term']);
            }
        }

        $vars['tax_query'] = self::withoutOurClauses((array) ($vars['tax_query'] ?? []));
        unset($vars['orderby'], $vars['order'], $vars['meta_key']);
        $vars = array_replace($vars, [
            'fields' => 'ids',
            'posts_per_page' => -1,
            'nopaging' => true,
            'paged' => 0,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'cache_results' => false,
            'suppress_filters' => false,
            'orderby' => 'none',
        ]);

        // A search plugin that takes over the MAIN search (Relevanssi) leaves this second query to WordPress's own search, which finds nothing the grid lists: every count
        // is 0 beside a grid full of products (tcs staging). Relevanssi takes over any query flagged `relevanssi`; the filter lets a site do the same for another plugin.
        $vars = self::searchBaseArgs($vars, function_exists('relevanssi_do_query'));
        $vars = apply_filters('pkde_kit/filter_counts_base_args', $vars, $wp_query);

        // WooCommerce's price clause only applies to the main query, which this is not: its own filter lets it apply.
        add_filter('woocommerce_enable_post_clause_filtering', '__return_true');
        $query = new WP_Query($vars);
        remove_filter('woocommerce_enable_post_clause_filtering', '__return_true');
        self::$queries['base'] = 1;

        return array_map('intval', $query->posts);
    }

    /**
     * The base query's arguments for a search: flagged `relevanssi` when Relevanssi is active, so it answers this query as it does the main one.
     *
     * @param  array<string, mixed>  $vars
     * @return array<string, mixed>
     */
    public static function searchBaseArgs(array $vars, bool $relevanssiActive): array
    {
        if (($vars['s'] ?? '') !== '' && $relevanssiActive) {
            $vars['relevanssi'] = true;
        }

        return $vars;
    }

    /** @param list<mixed> $taxQuery  @return list<mixed> */
    public static function withoutOurClauses(array $taxQuery): array
    {
        $kept = [];
        foreach ($taxQuery as $key => $clause) {
            if (is_array($clause) && ! empty($clause['pkde_filter'])) {
                continue;
            }
            $kept[$key] = $clause;
        }
        if (count(array_filter($kept, 'is_array')) < 2) {
            unset($kept['relation']);
        }

        return $kept;
    }

    /** The products of the base set with any of these terms (or a child of one). @param list<string> $slugs @param list<int> $base @return array<int, true> */
    private static function idsWithTerms(string $taxonomy, array $slugs, array $base): array
    {
        $terms = get_terms(['taxonomy' => $taxonomy, 'slug' => $slugs, 'hide_empty' => false]);
        if (! is_array($terms) || $terms === []) {
            return [];
        }

        $ttIds = [];
        foreach ($terms as $term) {
            $ttIds[(int) $term->term_taxonomy_id] = true;
            if (is_taxonomy_hierarchical($taxonomy)) {
                foreach (get_term_children($term->term_id, $taxonomy) as $childId) {
                    $child = get_term($childId, $taxonomy);
                    if ($child && ! is_wp_error($child)) {
                        $ttIds[(int) $child->term_taxonomy_id] = true;
                    }
                }
            }
        }

        return self::relatedIds(array_keys($ttIds), $base);
    }

    /** @param list<int> $ttIds  @param list<int> $base  @return array<int, true> */
    private static function relatedIds(array $ttIds, array $base): array
    {
        global $wpdb;

        $found = [];
        $in = implode(',', array_map('intval', $ttIds));
        foreach (array_chunk($base, self::CHUNK) as $chunk) {
            $ids = implode(',', $chunk);
            foreach ($wpdb->get_col("SELECT DISTINCT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($in) AND object_id IN ($ids)") as $id) {
                $found[(int) $id] = true;
            }
            self::$queries['choices'] = (self::$queries['choices'] ?? 0) + 1;
        }

        return $found;
    }

    /** The base set without the products WooCommerce marks out of stock. @param list<int> $base @return array<int, true> */
    private static function inStock(array $base): array
    {
        $out = get_term_by('name', 'outofstock', 'product_visibility');
        $outIds = $out ? self::relatedIds([(int) $out->term_taxonomy_id], $base) : [];

        return array_diff_key(array_fill_keys($base, true), $outIds);
    }

    /**
     * The products per term of these groups inside a scope. A hierarchical taxonomy counts a product once for every ancestor too.
     *
     * @param  list<array<string, mixed>>  $groups
     * @param  list<int>  $scope
     * @return array<string, array<int, int>>
     */
    private static function countTerms(array $groups, array $scope): array
    {
        global $wpdb;

        $out = [];
        $taxonomies = array_column($groups, 'source');
        $in = "'" . implode("','", array_map('esc_sql', $taxonomies)) . "'";
        $perTerm = [];

        foreach (array_chunk($scope, self::CHUNK) as $chunk) {
            $ids = implode(',', array_map('intval', $chunk));
            $rows = $wpdb->get_results("SELECT tr.object_id, tt.term_id, tt.taxonomy FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($in) AND tr.object_id IN ($ids)");
            self::$queries['counts'] = (self::$queries['counts'] ?? 0) + 1;

            foreach ($rows as $row) {
                $perTerm[$row->taxonomy][(int) $row->term_id][(int) $row->object_id] = true;
                if (is_taxonomy_hierarchical($row->taxonomy)) {
                    foreach (get_ancestors((int) $row->term_id, $row->taxonomy, 'taxonomy') as $ancestor) {
                        $perTerm[$row->taxonomy][(int) $ancestor][(int) $row->object_id] = true;
                    }
                }
            }
        }

        foreach ($taxonomies as $taxonomy) {
            $out[$taxonomy] = array_map('count', $perTerm[$taxonomy] ?? []);
        }

        return $out;
    }
}
