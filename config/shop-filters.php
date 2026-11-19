<?php

/**
 * The kit's defaults for the shop filters (WP-96). A site declares its own filters in config/shop-filters.php in its theme; Acorn merges the two, the site's
 * keys winning. Every key is documented in docs/woocommerce-filtering.md and docs/examples/shop-filters.php.
 *
 * 'groups' => null means "no declaration": the filters the kit always had, in this order: price, category, tags, brand (when the taxonomy exists), then
 * every global product attribute.
 */
return [
    'groups' => null,
    'hide_empty_terms' => false,
    'max_values_per_group' => 10,
    'max_active_groups' => 6,
    'counts_cache_ttl' => 3600,
];
