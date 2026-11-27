<?php

/**
 * A shop's filter declaration. Copy this file to config/shop-filters.php in the site's theme (Acorn reads the theme's config/ directory) and delete
 * what the shop does not need. Without the file the kit shows its defaults: price, category, tags, brand (when it has products), every global attribute.
 * Every key is optional; docs/woocommerce-filtering.md in the kit explains each one.
 */
return [
    // The groups, in the order the sidebar shows them.
    'groups' => [
        // A product taxonomy: product_cat, product_tag, product_brand, or a global attribute (pa_<name>).
        [
            'source' => 'product_cat',
            'label' => 'Category',      // default: the taxonomy's own label. For a translation use the `pkde_kit/shop_filter_label` filter or a gettext filter:
            // this file is read before WordPress has loaded its translations, so do not call __() here.
            'hierarchical' => true,     // children listed under their parents, indented (default false: a flat list). A parent's count always includes its children.
            'order' => 'menu_order',    // name (default) | menu_order (WooCommerce's own term order) | count (most products first)
            'limit' => null,            // show only the first N options (chosen options always stay); null = all
        ],
        ['source' => 'product_brand', 'label' => 'Brand'],
        ['source' => 'pa_colour', 'label' => 'Colour', 'order' => 'name'],
        ['source' => 'pa_material'],

        // The price range (minimum and maximum fields). Leave it out to have no price filter.
        ['source' => 'price', 'type' => 'range'],

        // Optional: a "show only products in stock" checkbox (?sage_filter_stock=1).
        // ['source' => 'stock', 'type' => 'toggle', 'label' => 'In stock only'],
    ],

    // false: an option nobody can reach from here stays visible but muted (its count is 0). true: it is left out (a chosen option always stays so it can be unticked).
    'hide_empty_terms' => false,

    // The request caps. A request that names more values than this for one group, or more groups than max_active_groups, is a 404 with no product query.
    // The shared .htaccess also answers 410 to 120 or more sage_filter_ arguments (pkde-deploy); keep max_active_groups x max_values_per_group under 120.
    'max_values_per_group' => 10,
    'max_active_groups' => 6,

    // How long the counts are cached, in seconds. They are also dropped when a product, its stock or a product term changes. 0 turns the cache off.
    'counts_cache_ttl' => 3600,
];
