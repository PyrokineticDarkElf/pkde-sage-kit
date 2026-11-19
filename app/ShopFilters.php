<?php

namespace App;

/**
 * The shop filters (WP-96): which groups a site declares (config/shop-filters.php, merged over the kit's defaults), the validated state of this request, and
 * the sidebar's query keys. The counts are ShopFilterCounts; the request checks (caps, canonical URL) are ShopFilterRequest.
 *
 * With no declaration the groups are the ones the kit always had: price, category, tags, brand (when the taxonomy exists), every global attribute.
 */
class ShopFilters
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /** @var list<array<string, mixed>>|null */
    private static ?array $groups = null;

    /** @var list<string> */
    private static array $invalid = [];

    /** @var array<string, mixed>|null */
    private static ?array $request = null;

    /** The resolved configuration: the kit's defaults, the site's file, then the `pkde_kit/shop_filters` filter. @return array<string, mixed> */
    public static function config(): array
    {
        if (self::$config === null) {
            $defaults = ['groups' => null, 'hide_empty_terms' => false, 'max_values_per_group' => 10, 'max_active_groups' => 6, 'counts_cache_ttl' => 3600];
            $declared = function_exists('config') ? config('shop-filters') : null;
            $config = array_replace($defaults, is_array($declared) ? $declared : []);
            $config = apply_filters('pkde_kit/shop_filters', $config);
            $config = array_replace($defaults, is_array($config) ? $config : []);
            $config['max_values_per_group'] = max(1, (int) $config['max_values_per_group']);
            $config['max_active_groups'] = max(1, (int) $config['max_active_groups']);
            $config['counts_cache_ttl'] = max(0, (int) $config['counts_cache_ttl']);
            self::$config = $config;
        }

        return self::$config;
    }

    /**
     * One declared group in its full form, or null when it names nothing usable. Pure apart from the two callables.
     *
     * @param  array<string, mixed>  $raw
     * @param  callable(string): bool  $isProductTaxonomy
     * @param  callable(string): string  $labelFor  the default label of a taxonomy
     * @return array<string, mixed>|null
     */
    public static function normaliseGroup(array $raw, callable $isProductTaxonomy, callable $labelFor): ?array
    {
        $source = trim((string) ($raw['source'] ?? ''));
        if ($source === '') {
            return null;
        }

        $order = (string) ($raw['order'] ?? 'name');
        $order = in_array($order, ['name', 'menu_order', 'count'], true) ? $order : 'name';
        $limit = isset($raw['limit']) && (int) $raw['limit'] > 0 ? (int) $raw['limit'] : null;

        if ($source === 'price') {
            return ['source' => 'price', 'type' => 'range', 'taxonomy' => null, 'label' => (string) ($raw['label'] ?? __('Price', 'sage')), 'hierarchical' => false, 'order' => $order, 'limit' => null];
        }
        if ($source === 'stock') {
            return ['source' => 'stock', 'type' => 'toggle', 'taxonomy' => null, 'label' => (string) ($raw['label'] ?? __('In stock only', 'sage')), 'hierarchical' => false, 'order' => $order, 'limit' => null];
        }
        if (! $isProductTaxonomy($source)) {
            return null;
        }

        return [
            'source' => $source,
            'type' => 'taxonomy',
            'taxonomy' => $source,
            'label' => isset($raw['label']) && (string) $raw['label'] !== '' ? (string) $raw['label'] : $labelFor($source),
            'hierarchical' => (bool) ($raw['hierarchical'] ?? false),
            'order' => $order,
            'limit' => $limit,
        ];
    }

    /** The groups of a site that declares none: what the kit always had. @return list<array<string, mixed>> */
    private static function undeclaredGroups(): array
    {
        $groups = [['source' => 'price'], ['source' => 'product_cat'], ['source' => 'product_tag']];

        if (taxonomy_exists('product_brand')) {
            $groups[] = ['source' => 'product_brand'];
        }
        foreach (wc_get_attribute_taxonomies() as $attribute) {
            $groups[] = ['source' => wc_attribute_taxonomy_name($attribute->attribute_name)];
        }

        return $groups;
    }

    private static function defaultLabel(string $taxonomy): string
    {
        return match ($taxonomy) {
            'product_cat' => __('Category', 'sage'),
            'product_tag' => __('Tags', 'sage'),
            'product_brand' => __('Brand', 'sage'),
            default => str_starts_with($taxonomy, 'pa_') ? wc_attribute_label($taxonomy) : (get_taxonomy($taxonomy)->labels->singular_name ?? $taxonomy),
        };
    }

    /**
     * The declared groups in the sidebar's order, each fully resolved. A source that is not a product taxonomy (nor price or stock) is left out and listed by
     * invalidSources(): the drift check fails it and an administrator sees a notice, but nothing breaks.
     *
     * @return list<array<string, mixed>>
     */
    public static function groups(): array
    {
        if (self::$groups !== null) {
            return self::$groups;
        }

        $declared = self::config()['groups'];
        $raw = is_array($declared) ? $declared : self::undeclaredGroups();
        $isProductTaxonomy = fn(string $source) => taxonomy_exists($source) && is_object_in_taxonomy('product', $source);
        $groups = [];
        $seen = [];
        self::$invalid = [];

        foreach ($raw as $one) {
            $one = is_array($one) ? $one : ['source' => (string) $one];
            $group = self::normaliseGroup($one, $isProductTaxonomy, [self::class, 'defaultLabel']);

            if ($group === null) {
                self::$invalid[] = (string) ($one['source'] ?? '(no source)');
            } elseif (! isset($seen[$group['source']])) {
                $seen[$group['source']] = true;
                $group['label'] = (string) apply_filters('pkde_kit/shop_filter_label', $group['label'], $group['source']);
                $groups[] = $group;
            }
        }

        return self::$groups = $groups;
    }

    /** The declared sources that are not usable (not a registered product taxonomy, price or stock). @return list<string> */
    public static function invalidSources(): array
    {
        self::groups();

        return array_values(array_unique(self::$invalid));
    }

    /** The group of a source, or null. @return array<string, mixed>|null */
    public static function group(string $source): ?array
    {
        foreach (self::groups() as $group) {
            if ($group['source'] === $source) {
                return $group;
            }
        }

        return null;
    }

    /** The taxonomy groups as taxonomy => label, in the sidebar's order (the form of this method before the declaration existed). @return array<string, string> */
    public static function taxonomies(): array
    {
        $taxonomies = [];
        foreach (self::groups() as $group) {
            if ($group['type'] === 'taxonomy') {
                $taxonomies[$group['source']] = $group['label'];
            }
        }

        return $taxonomies;
    }

    public static function queryKey(string $taxonomy): string
    {
        return ShopFilterRequest::PREFIX . $taxonomy;
    }

    /** Is the stock toggle declared? */
    public static function hasStockToggle(): bool
    {
        return self::group('stock') !== null;
    }

    /**
     * This request's filter state: validated (caps, known groups and values) and canonical. Computed once. 'status' is ok, redirect or notfound (see
     * ShopFilterRequest); filter-query.php acts on the last two before any product query runs.
     *
     * @return array{status: string, reason: string, selection: array<string, list<string>>, stock: bool, args: array<string, mixed>, filterArgs: array<string, mixed>}
     */
    public static function request(): array
    {
        if (self::$request === null) {
            $keys = [];
            foreach (self::taxonomies() as $taxonomy => $label) {
                $keys[self::queryKey($taxonomy)] = $taxonomy;
            }
            $config = self::config();

            self::$request = ShopFilterRequest::normalise(
                wp_unslash($_GET),
                $keys,
                self::hasStockToggle() ? self::queryKey('stock') : null,
                fn(string $value) => sanitize_title($value),
                function (string $taxonomy, array $slugs): array {
                    $found = get_terms(['taxonomy' => $taxonomy, 'slug' => $slugs, 'hide_empty' => false, 'fields' => 'slugs']);

                    return is_array($found) ? array_map('strval', $found) : [];
                },
                ['max_values_per_group' => $config['max_values_per_group'], 'max_active_groups' => $config['max_active_groups']],
            );
        }

        return self::$request;
    }

    /** Forget what was resolved for this request (tests, and a site that changes the filter after the first read). */
    public static function reset(): void
    {
        self::$config = self::$groups = self::$request = null;
        self::$invalid = [];
    }

    /** The validated, sorted term slugs chosen for a taxonomy. @return list<string> */
    public static function activeTerms(string $taxonomy): array
    {
        return self::request()['selection'][$taxonomy] ?? [];
    }

    /** Is "in stock only" chosen? */
    public static function inStockOnly(): bool
    {
        return self::request()['stock'];
    }

    /** The count beside each option of a taxonomy group: term_id => the products the grid shows after choosing that option alone (ShopFilterCounts). @return array<int, int> */
    public static function termCounts(string $taxonomy): array
    {
        return ShopFilterCounts::all()[$taxonomy] ?? [];
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

        if (self::inStockOnly() && ($stock = self::group('stock'))) {
            $chips[] = ['label' => $stock['label'], 'url' => $urlWithout(self::queryKey('stock'))];
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
