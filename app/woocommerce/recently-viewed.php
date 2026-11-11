<?php

/**
 * Recently viewed products (MGR-65, TPL-134). Opt-in: `add_filter('pkde_kit/recently_viewed', '__return_true')`.
 *
 * The pages stay cache-safe: a product page prints only an empty marker with the product's own id, and the box (the shop sidebar prints it, or
 * `recently_viewed_box()` anywhere) is an empty, hidden placeholder, the same for every visitor. resources/js/recently-viewed.js does the rest:
 *   - a visitor who is not logged in: the ids live in the browser's localStorage (first-party, functional, no cookie, nothing leaves the site);
 *   - a logged-in visitor: the browser posts its list once per page to `?wc-ajax=pkde_recent_sync`, the server merges it into the user meta
 *     `_pkde_recently_viewed` (so the list follows the account across devices) and sends the merged ids back;
 *   - `?wc-ajax=pkde_recent` (GET, never page-cached, nothing stored) returns the next products as small list items, skipping what is hidden,
 *     out of stock or deleted and the product being viewed.
 *
 * Filters: pkde_kit/recently_viewed (bool, false), pkde_kit/recently_viewed_count (int, 5 per batch), pkde_kit/recently_viewed_max (int, 20 kept).
 * Cookie policy: localStorage is functional storage the visitor's own browsing history needs; no third party, no tracking id. docs/recently-viewed.md.
 */

namespace App;

/** How many ids are kept (20 unless `pkde_kit/recently_viewed_max`). */
function recent_max(): int
{
    return function_exists('apply_filters') ? max(1, min(50, (int) apply_filters('pkde_kit/recently_viewed_max', 20))) : 20;
}

/**
 * Product ids from a request or a stored value (pure): an array or "5,9,12" in, positive integers out, each once, in order, at most $max.
 *
 * @return list<int>
 */
function recent_ids(mixed $raw, int $max = 20): array
{
    $parts = is_array($raw) ? $raw : explode(',', is_scalar($raw) ? (string) $raw : '');
    $ids = [];

    foreach ($parts as $part) {
        $id = is_scalar($part) && preg_match('/^\d{1,12}$/', trim((string) $part)) ? (int) trim((string) $part) : 0;

        if ($id > 0 && ! in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return array_slice($ids, 0, max(0, $max));
}

/**
 * The list after viewing: what the browser has (newest first) followed by what the account had, each id once, capped (pure).
 *
 * @param  list<int>  $browser
 * @param  list<int>  $account
 * @return list<int>
 */
function recent_merge(array $browser, array $account, int $max = 20): array
{
    return recent_ids(array_merge($browser, $account), $max);
}

/** The ids to show (pure): the viewed product left out, then the batch (offset and count). @return array{ids: list<int>, total: int} */
function recent_batch(array $ids, int $exclude, int $offset, int $count): array
{
    $ids = array_values(array_filter($ids, fn($id) => $id !== $exclude));

    return ['ids' => array_slice($ids, max(0, $offset), max(0, $count)), 'total' => count($ids)];
}

if (! function_exists('add_action') || ! class_exists('WooCommerce')) {
    return;
}

function recently_viewed_enabled(): bool
{
    return (bool) apply_filters('pkde_kit/recently_viewed', false);
}

/** The placeholder box (printed where the site wants it; the shop sidebar does). Empty and hidden until the script fills it. */
function recently_viewed_box(): string
{
    if (! recently_viewed_enabled()) {
        return '';
    }

    $count = max(1, min(12, (int) apply_filters('pkde_kit/recently_viewed_count', 5)));

    return view('pkde-kit::partials.recently-viewed', [
        'url' => \WC_AJAX::get_endpoint('pkde_recent'),
        'syncUrl' => \WC_AJAX::get_endpoint('pkde_recent_sync'),
        'count' => $count,
        'max' => recent_max(),
    ])->render();
}

// A product page records itself: an empty marker with the product's own id (the same for every visitor).
add_action('woocommerce_after_single_product', function () {
    global $product;

    if (recently_viewed_enabled() && $product instanceof \WC_Product) {
        printf('<span hidden data-pkde-recent-view="%d" data-sync-url="%s" data-max="%d"></span>', $product->get_id(), esc_url(\WC_AJAX::get_endpoint('pkde_recent_sync')), recent_max());
    }
});

// The visible products of a list of ids, as small list items.
add_action('wc_ajax_pkde_recent', function () {
    if (! recently_viewed_enabled()) {
        wp_send_json(['html' => '', 'total' => 0, 'shown' => 0]);
    }

    $count = max(1, min(12, absint($_GET['count'] ?? 5))); // phpcs:ignore WordPress.Security.NonceVerification
    $offset = absint($_GET['offset'] ?? 0); // phpcs:ignore WordPress.Security.NonceVerification
    $exclude = absint($_GET['exclude'] ?? 0); // phpcs:ignore WordPress.Security.NonceVerification
    $ids = recent_ids($_GET['ids'] ?? '', recent_max()); // phpcs:ignore WordPress.Security.NonceVerification

    // Unavailable products (hidden, private, out of stock when the shop hides them, deleted) are dropped before counting, so "show more" is honest.
    $products = array_values(array_filter(array_map('wc_get_product', $ids), fn($p) => $p && $p->is_visible() && $p->is_in_stock()));
    $visible = recent_batch(array_map(fn($p) => $p->get_id(), $products), $exclude, $offset, $count);
    $byId = [];

    foreach ($products as $p) {
        $byId[$p->get_id()] = $p;
    }

    $lazy = static fn(array $attributes): array => $attributes + ['loading' => 'lazy', 'decoding' => 'async'];
    add_filter('wp_get_attachment_image_attributes', $lazy, 20);
    $html = '';

    foreach ($visible['ids'] as $id) {
        $html .= view('pkde-kit::partials.recently-viewed-item', ['product' => $byId[$id]])->render();
    }

    remove_filter('wp_get_attachment_image_attributes', $lazy, 20);
    nocache_headers();
    wp_send_json(['html' => $html, 'total' => $visible['total'], 'shown' => count($visible['ids'])]);
});

// A logged-in visitor's list follows the account. POST only, same origin only (no nonce: a nonce is per user and would make the page per-visitor, so the
// browser's own Sec-Fetch-Site header is the check); it stores product ids and nothing else.
add_action('wc_ajax_pkde_recent_sync', function () {
    $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin'));

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ! in_array($fetchSite, ['same-origin', 'none'], true)) {
        wp_send_json(['ids' => []], 400);
    }

    $browser = recent_ids($_POST['ids'] ?? '', recent_max()); // phpcs:ignore WordPress.Security.NonceVerification

    if (! recently_viewed_enabled() || ! is_user_logged_in()) {
        wp_send_json(['ids' => $browser]);
    }

    $merged = recent_merge($browser, recent_ids(get_user_meta(get_current_user_id(), '_pkde_recently_viewed', true), recent_max()), recent_max());
    update_user_meta(get_current_user_id(), '_pkde_recently_viewed', $merged);
    nocache_headers();
    wp_send_json(['ids' => $merged]);
});
