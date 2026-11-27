<?php

/**
 * Helpers of the shop code: the pure ones first (the kit's own tests load them without WordPress), then the ones that need WordPress.
 * Loaded by Composer (autoload.files) like helpers.php, so they exist wherever the kit does; they only do anything with WooCommerce.
 */

namespace App;

/**
 * The meta field a WooCommerce sort choice orders by when Relevanssi answers the query (price, popularity, rating), or null.
 */
function search_sort_field(?string $orderby): ?string
{
    return ['price' => '_price', 'popularity' => 'total_sales', 'rating' => '_wc_average_rating'][$orderby ?? ''] ?? null;
}

/**
 * Is this a filtered or sorted view of a product list (a shop filter choice, a price range, a sort order or a
 * ?sage_view=sale|new)? Such URLs are combinations of the same products, so they are served noindex, follow: as an
 * X-Robots-Tag header, as a wp_robots tag and in Rank Math's robots meta (app/woocommerce/filter-query.php). This is the
 * ONE place that says which query arguments make a URL a faceted one.
 *
 * @param  array<string, mixed>  $query  the request's query arguments ($_GET)
 */
function is_faceted_request(array $query): bool
{
    foreach (array_keys($query) as $key) {
        if (in_array((string) $key, ['sage_view', 'min_price', 'max_price', 'orderby'], true) || str_starts_with((string) $key, 'sage_filter_')) {
            return true;
        }
    }

    return false;
}

/**
 * The Product schema offers with the prices a shopper SEES (S-1): Rank Math prints the stored price, so a shop that displays prices with tax
 * (or without) while it stores them the other way round showed one price on the page and another in the markup. Offer: price, and the price and
 * valueAddedTaxIncluded of its priceSpecification. AggregateOffer: lowPrice and highPrice. A price that is null is left as Rank Math printed it.
 *
 * @param  array<string, mixed>  $offers  the entity's `offers`
 * @param  bool  $vatIncluded  the shop displays prices including tax
 * @return array<string, mixed>
 */
function schema_offer_prices(array $offers, ?string $price, ?string $low, ?string $high, bool $vatIncluded): array
{
    if (($offers['@type'] ?? '') === 'AggregateOffer') {
        return array_replace($offers, array_filter(['lowPrice' => $low, 'highPrice' => $high], fn($v) => $v !== null));
    }

    if ($price !== null) {
        $offers['price'] = $price;
    }

    if (isset($offers['priceSpecification']) && is_array($offers['priceSpecification'])) {
        $offers['priceSpecification'] = array_replace($offers['priceSpecification'], array_filter(['price' => $price], fn($v) => $v !== null), ['valueAddedTaxIncluded' => $vatIncluded ? 'true' : 'false']);
    }

    return $offers;
}

/**
 * schema.org OfferShippingDetails from what the store has set: a rate and the countries it applies to. Nothing when there is no rate.
 *
 * @param  list<string>  $countries  ISO country codes
 * @return array<string, mixed>
 */
function shipping_details_schema(?string $rate, string $currency, array $countries): array
{
    if ($rate === null || $currency === '' || $countries === []) {
        return [];
    }

    return [
        '@type' => 'OfferShippingDetails',
        'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => $rate, 'currency' => $currency],
        'shippingDestination' => array_map(fn($c) => ['@type' => 'DefinedRegion', 'addressCountry' => $c], array_values($countries)),
    ];
}

/**
 * Rank Math's robots directives with a faceted view made `noindex, follow`; other directives and other URLs unchanged.
 *
 * @param  array<string, string>  $robots  Rank Math's directives, keyed (index, follow, max-snippet, ...)
 * @param  array<string, mixed>  $query  the request's query arguments ($_GET)
 * @return array<string, string>
 */
function faceted_rank_math_robots(array $robots, array $query): array
{
    return is_faceted_request($query) ? ['index' => 'noindex', 'follow' => 'follow'] + $robots : $robots;
}

if (! function_exists('add_filter')) {
    return; // loaded outside WordPress (unit tests): the functions above are all there is
}

/**
 * A photograph for a product category that has no thumbnail of its own: the most popular product in it (a product that is in
 * stock when there is one). Cached for a day; an empty string when there is none or WooCommerce is not active.
 */
function category_menu_image(int $termId): string
{
    $key = 'menu_image_' . $termId;
    $cached = get_transient($key);
    if ($cached !== false) {
        return (string) $cached;
    }

    $url = '';
    $term = get_term($termId, 'product_cat');
    if ($term instanceof \WP_Term && function_exists('wc_get_products')) {
        foreach (['instock', ''] as $stock) {
            $args = ['limit' => 1, 'status' => 'publish', 'visibility' => 'catalog', 'category' => [$term->slug], 'orderby' => 'popularity', 'order' => 'DESC'] + ($stock ? ['stock_status' => $stock] : []);
            $product = wc_get_products($args)[0] ?? null;
            $url = $product ? (string) wp_get_attachment_image_url($product->get_image_id(), 'medium_large') : '';
            if ($url) {
                break;
            }
        }
    }
    set_transient($key, $url, DAY_IN_SECONDS);

    return $url;
}

/**
 * The tax_query clause that leaves out products WooCommerce hides from the catalogue (catalogue visibility "hidden" or "search only"; they carry the
 * `exclude-from-catalog` term of `product_visibility`). WP_Query and get_posts() do not do this on their own, only WooCommerce's main shop query does:
 * every kit query that lists or counts products for visitors adds this (shop filter counts, the newest and best-selling blocks, the "new" badge rule).
 *
 * @return array{taxonomy: string, field: string, terms: list<string>, operator: string}
 */
function catalog_visibility_clause(): array
{
    return ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude-from-catalog'], 'operator' => 'NOT IN'];
}
