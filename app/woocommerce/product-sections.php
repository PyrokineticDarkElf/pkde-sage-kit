<?php

/**
 * The product page's "more products" sections (MGR-79): upsells, cross-sells and related products, in that order, each a scroll-snap carousel of
 * the site's own product card.
 *
 *   - Upsells ("You may also like"): alternatives while the visitor is still deciding. Cross-sells ("Goes well with"): the add-ons set on this
 *     product (WooCommerce only shows them in the basket, where they stay). Related ("More from <category>"): the same category and tags.
 *   - A section prints ONLY when it has visible products, so there is never an empty heading. A product shown in an earlier section is not
 *     repeated in a later one (`pkde_kit/product_sections_dedupe`, default true).
 *   - Nothing here depends on the visitor: the ids come from the product's own links (and WooCommerce's per-product related cache), so the page
 *     cache can keep serving one copy. docs/product-sections.md.
 *
 * Filters (all optional):
 *   pkde_kit/product_sections_enabled (bool, true)      false leaves WooCommerce's own upsells and related output alone (no carousels)
 *   pkde_kit/product_sections (list)                    the section keys in order; remove one to switch it off. Keys: upsells, cross-sells, related
 *   pkde_kit/product_section_title (string, key, WC_Product)    the heading
 *   pkde_kit/product_section_count (int, key, WC_Product)       how many products (upsells 8, cross-sells 8, related 12; 0 switches it off)
 *   pkde_kit/product_section_order (array [orderby, order], key)  ['none', 'asc'] keeps a hand-picked list's order; related is ['rand', 'desc']
 *   pkde_kit/related_load_more (int, WC_Product)        products added per "Show more" click on the related section (0, the default, = no button)
 *   pkde_kit/product_sections_dedupe (bool, true)
 */

namespace App;

/** The section keys the kit knows, in the default order. */
const PRODUCT_SECTION_KEYS = ['upsells', 'cross-sells', 'related'];

/**
 * The sections to print, in order (pure): the configured list reduced to known keys, each once.
 *
 * @param  array<mixed>  $configured
 * @return list<string>
 */
function product_section_keys(array $configured): array
{
    return array_values(array_unique(array_filter($configured, fn($key) => is_string($key) && in_array($key, PRODUCT_SECTION_KEYS, true))));
}

/** The default heading of a section (pure). $category is the product's deepest category name, for "More from ...". */
function product_section_default_title(string $key, string $category = ''): string
{
    $t = fn(string $text) => function_exists('__') ? __($text, 'sage') : $text;

    return match ($key) {
        'upsells' => $t('You may also like'),
        'cross-sells' => $t('Goes well with'),
        default => $category !== '' ? sprintf($t('More from %s'), $category) : $t('Related products'),
    };
}

/** The default product count of a section (pure). */
function product_section_default_count(string $key): int
{
    return $key === 'related' ? 12 : 8;
}

/**
 * The product ids of a section after the earlier sections' ids are taken out and the count applied (pure).
 *
 * @param  list<int>  $ids
 * @param  list<int>  $shown  ids already shown in an earlier section
 * @return list<int>
 */
function product_section_ids(array $ids, array $shown, int $count, bool $dedupe = true): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));

    if ($dedupe) {
        $ids = array_values(array_diff($ids, array_map('intval', $shown)));
    }

    return $count > 0 ? array_slice($ids, 0, $count) : [];
}

if (! function_exists('add_action') || ! class_exists('WooCommerce')) {
    return;
}

/** The deepest category name of a product, for the related heading. */
function product_section_category_name(\WC_Product $product): string
{
    $terms = get_the_terms($product->get_id(), 'product_cat');

    if (! is_array($terms) || $terms === []) {
        return '';
    }

    $term = deepest_term($terms, fn($t) => count(get_ancestors($t->term_id, 'product_cat', 'taxonomy')));

    return $term ? (string) $term->name : '';
}

/** The candidate ids of one section for a product, before the earlier sections are taken out. @return list<int> */
function product_section_candidates(string $key, \WC_Product $product, int $count, array $shown, array $extraExclude = []): array
{
    return match ($key) {
        'upsells' => array_map('intval', $product->get_upsell_ids()),
        'cross-sells' => array_map('intval', $product->get_cross_sell_ids()),
        // WooCommerce's own related query (cached per product, never per visitor); the earlier sections and the product itself are excluded.
        default => array_map('intval', wc_get_related_products($product->get_id(), max(1, $count) + count($shown) + count($extraExclude), array_merge($product->get_upsell_ids(), $shown, $extraExclude))),
    };
}

/** The visible product objects of a section, in the configured order. @return list<\WC_Product> */
function product_section_products(string $key, \WC_Product $product, array &$shown, array $extraExclude = []): array
{
    $count = (int) apply_filters('pkde_kit/product_section_count', product_section_default_count($key), $key, $product);
    $dedupe = (bool) apply_filters('pkde_kit/product_sections_dedupe', true);
    $ids = product_section_ids(product_section_candidates($key, $product, $count, $shown, $extraExclude), $dedupe ? $shown : [], $count + 20, $dedupe);

    $products = array_filter(array_map('wc_get_product', $ids), 'wc_products_array_filter_visible');
    $products = array_filter($products, fn($p) => $p->get_id() !== $product->get_id());

    [$orderby, $order] = apply_filters('pkde_kit/product_section_order', $key === 'related' ? ['rand', 'desc'] : ['none', 'asc'], $key) + [1 => 'asc'];

    if ($orderby !== 'none') {
        $products = wc_products_array_orderby($products, $orderby, $order);
    }

    $products = array_slice(array_values($products), 0, max(0, $count));
    $shown = array_merge($shown, array_map(fn($p) => $p->get_id(), $products));

    return $products;
}

/** The cards of a list of products: the site's own `content-product` card, in a list the carousel scrolls. */
function product_section_cards(array $products, string $key): string
{
    // Images are below the fold and (past the first row) off the side: lazy, whatever WordPress decided for the first image on the page.
    $lazy = static fn(array $attributes): array => $attributes + ['loading' => 'lazy', 'decoding' => 'async'];
    add_filter('wp_get_attachment_image_attributes', $lazy, 20);

    wc_set_loop_prop('name', $key);
    ob_start();

    foreach ($products as $item) {
        $GLOBALS['post'] = get_post($item->get_id()); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        setup_postdata($GLOBALS['post']);
        wc_get_template_part('content', 'product');
    }

    $html = (string) ob_get_clean();
    wp_reset_postdata();
    wc_reset_loop();
    remove_filter('wp_get_attachment_image_attributes', $lazy, 20);

    return $html;
}

/** Prints the sections of the product on the page. */
function print_product_sections(): void
{
    global $product;

    if (! $product instanceof \WC_Product) {
        return;
    }

    $shown = [$product->get_id()];
    $keys = product_section_keys((array) apply_filters('pkde_kit/product_sections', PRODUCT_SECTION_KEYS));

    foreach ($keys as $key) {
        $products = product_section_products($key, $product, $shown);

        if ($products === []) {
            continue;
        }

        $title = (string) apply_filters(
            'pkde_kit/product_section_title',
            product_section_default_title($key, $key === 'related' ? product_section_category_name($product) : ''),
            $key,
            $product,
        );
        $more = $key === 'related' ? max(0, (int) apply_filters('pkde_kit/related_load_more', 0, $product)) : 0;

        echo view('pkde-kit::partials.product-section', [
            'key' => $key,
            'title' => $title,
            'cards' => product_section_cards($products, $key),
            'moreCount' => $more,
            'moreUrl' => $more ? add_query_arg(['product_id' => $product->get_id(), 'count' => $more], \WC_AJAX::get_endpoint('pkde_related')) : '',
            'moreLabel' => __('Show more', 'sage'),
            'prevLabel' => __('Previous products', 'sage'),
            'nextLabel' => __('Next products', 'sage'),
        ])->render();
    }
}

// Swap WooCommerce's upsell and related output for the kit's sections (the basket's cross-sells are another hook and stay).
add_action('wp', function () {
    if (! is_product() || ! apply_filters('pkde_kit/product_sections_enabled', true)) {
        return;
    }

    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    add_action('woocommerce_after_single_product_summary', __NAMESPACE__ . '\\print_product_sections', 15);
});

// "Show more" on the related section: the next products as the site's own cards (HTML, not JSON, so the card is exactly the page's). GET and the
// same for every visitor; WooCommerce's ajax endpoint is never page-cached.
add_action('wc_ajax_pkde_related', function () {
    $product = wc_get_product(absint($_GET['product_id'] ?? 0)); // phpcs:ignore WordPress.Security.NonceVerification
    $count = min(24, max(1, absint($_GET['count'] ?? 0))); // phpcs:ignore WordPress.Security.NonceVerification
    $exclude = array_map('absint', array_filter(explode(',', (string) ($_GET['exclude'] ?? '')))); // phpcs:ignore WordPress.Security.NonceVerification

    if (! $product || ! $product->is_visible() || (int) apply_filters('pkde_kit/related_load_more', 0, $product) < 1) {
        wp_send_json(['html' => '', 'done' => true]);
    }

    $shown = [$product->get_id()];
    $ids = array_map('intval', wc_get_related_products($product->get_id(), $count + count($exclude), array_merge($product->get_upsell_ids(), $product->get_cross_sell_ids(), $exclude)));
    $products = array_slice(array_values(array_filter(array_map('wc_get_product', array_diff($ids, $exclude)), 'wc_products_array_filter_visible')), 0, $count);

    wp_send_json(['html' => product_section_cards($products, 'related'), 'done' => count($products) < $count, 'shown' => $shown]);
});
