<?php

namespace App;

/*
|--------------------------------------------------------------------------
| Product search with live results (optional: Relevanssi and Relevanssi Live Ajax Search)
|--------------------------------------------------------------------------
|
| tcs and bb carry the same code, so it lives here. Everything below does nothing while the two plugins are inactive (their filters never
| fire); a site that wants it requires the plugins (`wpackagist-plugin/relevanssi` and `relevanssi-live-ajax-search`, the free editions),
| runs `wp eval-file vendor/pkde/sage-kit/bin/relevanssi-settings.php` once per environment, and prints the form with
| `@include('pkde-kit::partials.product-search')` (docs/woocommerce-templates.md, "Search").
*/

if (! function_exists('add_filter')) {
    return;
}

// Relevanssi answers the query itself, so WooCommerce's SQL sorting never runs: hand the shop's sort choices over as the meta fields they sort by.
add_filter('relevanssi_modify_wp_query', function (\WP_Query $query): \WP_Query {
    $orderby = $query->get('orderby');
    $first = is_string($orderby) ? strtok($orderby, ' ') : null;
    $field = search_sort_field($first);

    if ($field) {
        $query->set('meta_key', $field);
        $query->set('orderby', 'meta_value_num');
    } elseif ($first === 'date') {
        $query->set('orderby', 'post_date'); // WooCommerce's "date ID", which Relevanssi does not read
    }

    return $query;
});

// The live results take the theme's styling (resources/css/live-search.css); the plugin keeps only its small inline base styles.
add_action('wp_enqueue_scripts', fn() => wp_dequeue_style('relevanssi-live-search'), 20);
add_filter('relevanssi_live_search_configs', function (array $configs): array {
    $configs['default']['results']['width'] = 'css';

    return $configs;
});
add_filter('relevanssi_live_search_posts_per_page', fn() => max(1, (int) apply_filters('pkde_kit/live_search_results', 6)));

// The results markup is the kit's Blade view; the plugin looks for a template in the theme (relevanssi-live-ajax-search/search-results.php),
// which still wins when a site has one.
add_filter('relevanssi_live_search_results_template', function ($located) {
    // $located is the plugin's own default when the theme has no template of its own; only a theme template keeps the lead.
    return locate_template('relevanssi-live-ajax-search/search-results.php') ? $located : dirname(__DIR__) . '/resources/live-search/search-results.php';
});

// A product search (?s=...&post_type=product, as the header's form sends) lists products in the shop's template, with its filters, sorting and
// product cards. Other searches keep search.php.
add_filter('search_template_hierarchy', function (array $templates): array {
    if (function_exists('is_post_type_archive') && is_post_type_archive('product')) {
        array_unshift($templates, 'archive-product.php');
    }

    return $templates;
}, 9);
