<?php

/**
 * The Sale and New badges of a product (tcs's, as the kit's, owner via the manager, MGR-77): one partial, `partials/product-badges`, for the card
 * and the product page, one look (`.pkde-badge`, the --pkde-badge-* variables in resources/css/shop-pages.css), one rule for "New".
 *
 *   - Sale: the product is on sale. New: among the N newest products (4, `pkde_kit/recent_counts`), or, when `pkde_kit/new_badge_days` is above 0,
 *     published within that many days (App\RecentContent; the ?sage_view=new list follows the same rule).
 *   - On archives and searches a badge links to that view of the shop (?sage_view=sale or new); on a curated row it is a plain label.
 *   - `pkde_kit/product_badges` (list of badges, WC_Product) adds, removes or reorders: a badge is ['key', 'label', 'url' (or ''), 'aria', 'name'].
 */

namespace App;

/**
 * The badges from plain facts (pure).
 *
 * @return list<array{key: string, label: string, url: string, aria: string, name: string}>
 */
function badge_items(bool $onSale, bool $isNew, string $name, string $saleUrl, string $newUrl, bool $links): array
{
    $t = fn(string $text) => function_exists('__') ? __($text, 'sage') : $text;
    $badges = [];

    if ($onSale) {
        $badges[] = ['key' => 'sale', 'label' => $t('Sale'), 'url' => $links ? $saleUrl : '', 'aria' => sprintf('%s: %s (%s)', $t('Sale'), $name, $t('see all products on sale')), 'name' => $name];
    }
    if ($isNew) {
        $badges[] = ['key' => 'new', 'label' => $t('New'), 'url' => $links ? $newUrl : '', 'aria' => sprintf('%s: %s (%s)', $t('New'), $name, $t('see the newest products')), 'name' => $name];
    }

    return $badges;
}

/** The badges of a product; $links null = link on archives and searches. */
function product_badges($product, ?bool $links = null): array
{
    $links ??= is_shop() || is_product_taxonomy() || is_search();
    $shop = wc_get_page_permalink('shop');
    $badges = badge_items(
        (bool) $product->is_on_sale(),
        RecentContent::isNew((int) $product->get_id(), 'product'),
        (string) $product->get_name(),
        add_query_arg('sage_view', 'sale', $shop),
        add_query_arg('sage_view', 'new', $shop),
        (bool) $links,
    );

    return (array) apply_filters('pkde_kit/product_badges', $badges, $product);
}
