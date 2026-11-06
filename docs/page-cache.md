# Page cache and visitors with a basket (MGR-76)

The W3 Total Cache standard (pkde-deploy) used to skip the page cache for anyone with a basket: the cookies `woocommerce_items_in_cart` and
`wp_woocommerce_session_` made every page, blog included, build in PHP for them. The kit now keeps every page free of personal content, so a site
can opt in to serving cached pages to those visitors too.

## The rule

**A page the page cache may store carries nothing that belongs to one visitor.** The page cache stores whatever response a visitor triggers, so a
page built for a visitor with a basket and stored would be served to everyone. Everything personal is therefore filled in by the browser, from an
uncached request, or marks its own page uncacheable.

## What the kit does

| Personal bit | Where | How it is cache-safe |
|---|---|---|
| Header basket count | `\App\cart_count_html()` | A page always prints the EMPTY count (`$live = false`). `resources/js/cart-badge.js` fills it in: while the cookie `woocommerce_items_in_cart` exists it POSTs `?wc-ajax=get_refreshed_fragments` (never cached) and swaps the count in; without the cookie it clears it. After an AJAX add WooCommerce's own script swaps the fragment, which is the only place the real number is built (`cart_count_html(true)`). |
| WooCommerce notices | `partials/woocommerce-notices` | Printed by `\App\print_notices_uncached()`: a page with a pending notice is marked uncacheable (`\App\no_page_cache()`: `DONOTCACHEPAGE` and no-cache headers). A notice shows on the response of the request that made it (an add to basket by POST or `?add-to-cart=`, which no page cache stores), never on a cached copy. |
| Add to basket | product cards | AJAX (WooCommerce's `ajax_add_to_cart`, setting WooCommerce > Settings > Products > "Enable AJAX add to cart buttons on archives" must be on); no reload, no notice. On a product page the form posts and the answer is the POST response, which is not cached. |
| Cart, checkout, my account | WooCommerce | Uncached by WooCommerce itself (`DONOTCACHEPAGE`). Their nonces are printed only there. |
| Recently viewed (MGR-65) | not built yet | Must be built in the browser (localStorage plus an uncached request); never from a cookie on the server. |

A site that prints anything else per visitor calls `\App\no_page_cache()` in the same request, or moves it to the browser.

## What a site must check before it opts in

1. **Its header prints the kit's count** (`{!! \App\cart_count_html() !!}` inside `data-pkde-cart-badge`), not `WC()->cart->get_cart_contents_count()`. (rp, bp, mds and jsd are not shops: the `WC()->cart` block in their headers is dead code to delete, and the opt-in is meaningless without WooCommerce, so the drift check ignores them.)
2. No view reads the basket, a notice, a cookie or the user on the server. The drift check scans the theme's views for it (`pkde_drift_personal_views`) and FAILs while the opt-in is on.
3. WooCommerce settings: tax by the shop's base address (a customer-specific tax address changes prices per visitor), geolocation off or "Geolocate (with page caching support)", AJAX add to cart on, "Redirect to the cart page after successful addition" either way (the cart page is uncached).
4. No plugin prints per-visitor content (checked 2026-11-06 on tcs: Contact Form 7, Turnstile, Wordfence, Rank Math, Relevanssi, Post SMTP, W3TC, WPS Hide Login: no basket, user or session data in the page; Relevanssi live search is an AJAX request).

## Opting in

On the server (staging first), in the site's release directory:

```bash
bash bin/deploy/wp.sh option update pkde_cache_basket_visitors 1
bash bin/deploy/wp.sh eval-file bin/deploy/w3tc-settings.php
```

The settings script then takes the two cookies out of W3TC's reject list; the drift check requires the list and the theme to match (rows
"W3TC page cache serves visitors with a basket (opted in)" and the two "Cache-safe theme" rows). To go back: `wp option delete pkde_cache_basket_visitors` and re-run the settings script.
