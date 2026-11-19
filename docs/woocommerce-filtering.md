# WooCommerce filtering

## Current implementation

The shop sidebar is a custom, progressively enhanced GET form. It supports
multi-select product categories, tags, brands, and global product attributes,
a price range and an in-stock toggle, as a shop declares them (see "Declaring a
shop's filters" below). Choices are sent once, with the Apply button; without
JavaScript the form still produces a normal shareable URL.

The implementation is split across:

| File | Responsibility |
| --- | --- |
| `resources/views/partials/sidebar-shop.blade.php` | One shared filter form, price inputs, clear state, and all taxonomy filter groups. |
| `resources/views/partials/shop-term-filter.blade.php` | Checkbox group markup and dynamic count display. |
| `app/ShopFilters.php` | The declared groups (`config/shop-filters.php`), query parameter names and this request's validated choices. |
| `app/ShopFilterRequest.php` | The checks made before any product query: caps, unknown groups and values, the one canonical URL (pure, tested without WordPress). |
| `app/ShopFilterCounts.php` | The count beside each option, in a bounded number of queries, cached. |
| `config/shop-filters.php` | The kit's defaults, merged under the site's own file by `KitServiceProvider`. |
| `app/woocommerce/filter-query.php` | Checks the request before any product query (404 or 301), applies the validated choices to the main product query (flagged clauses the counts can take out again), invalidates the cached counts, and adds `?featured=1` support (see below). |
| `resources/js/kit.js` | Omits blank price values on submit. Nothing is sent when a box is ticked. |
| `resources/js/shop-filters.js` | Counts the choices that differ from the applied state for the "Apply filters (3)" label and the screen-reader status line; it cannot block a submit. |
| `resources/css/shop-filter-actions.css` | The "Apply filters" and "Reset" buttons (brand tokens, `--pkde-filter-*` variables, sticky bar below lg). |

**Apply and Reset (WP-89).** A tick or a typed price only changes the form; the choices are sent once, with the "Apply filters" button (a plain GET submit, so it works without JavaScript, and Enter in a price field submits too). "Reset" is a link to the archive without the form's choices (`sage_filter_*`, `min_price`, `max_price`, `paged`; `orderby` and every other argument stay) when some are applied, and a native reset button (back to the rendered, unfiltered state, no request) when none are. The counts beside the options always describe the applied filters, never the pending ticks. The grid/list choice lives in localStorage, not in the URL, so Reset never touches it. Layout: from lg up the floating column is capped at the screen height left under the sticky header and only the groups (`.pkde-filter-groups`) scroll inside it, with a thin scrollbar that appears only on real overflow; the Apply bar is sticky at the bottom of the viewport (below lg and on short screens) or of the column, and is never inside the scroller.

`?featured=1` on the Shop page is a separate thing from the sidebar filters above — it powers the
Featured Products block's own "View all featured" link
(`../resources/blocks/featured-products/render.php`), since WooCommerce's classic shop template has
no built-in URL filter for "featured only" otherwise (that's a block-theme/shortcode-only feature).
`filter-query.php` reuses `wc_get_featured_product_ids()` — the same source the block itself queries
from — so the linked page genuinely shows the same set the block already proved has content, not a
second, possibly-different definition of "featured".

## URL contract

Theme-owned taxonomy parameters are deliberately prefixed with `sage_filter_`.
WooCommerce reserves several `filter_*` names for its own scalar query values;
using those names with PHP arrays can cause request failures.

Examples:

```text
/shop/?sage_filter_product_cat[]=chairs&sage_filter_product_cat[]=tables
/shop/?sage_filter_product_tag[]=new&sage_filter_pa_colour[]=blue
/shop/?min_price=50&max_price=200&sage_filter_product_brand[]=acme
```

Within a filter group, multiple selected terms use `IN` behavior: a product may
match any selected term. Between different filter groups, the query uses `AND`:
a product must match every selected group. Price parameters are WooCommerce’s
native `min_price` and `max_price` values and combine with the taxonomy query.

The earlier `filter_product_cat[]` and `filter_product_tag[]` array URLs are no longer migrated
(dropped in 2026.11.19.1): WooCommerce reserves `filter_*` for its own layered navigation, so they
now do nothing. Theme code must only emit `sage_filter_*` taxonomy parameters.

## Declaring a shop's filters (WP-96)

A shop lists its filters in `config/shop-filters.php` in its theme (Acorn reads the theme's `config/` directory). It is a plain PHP file that returns an array;
a commented example is `docs/examples/shop-filters.php` in the kit. The kit's own `config/shop-filters.php` holds the defaults and is merged under the site's
file, so a site names only what differs.

| Key | Default | Meaning |
| --- | --- | --- |
| `groups` | `null` | The groups in the sidebar's order. `null` (no file) means what the kit always had: price, category, tags, brand (when it has products), every global attribute. |
| `groups[].source` | required | A product taxonomy (`product_cat`, `product_tag`, `product_brand`, `pa_<attribute>`), `price` or `stock`. |
| `groups[].label` | the taxonomy's label | The heading. Not translated here (the file is read before translations load): use the `pkde_kit/shop_filter_label` filter (`string $label, string $source`) or a gettext filter. |
| `groups[].type` | from the source | `range` for price, `toggle` for stock, otherwise a checkbox list. |
| `groups[].hierarchical` | `false` | List children under their parents, indented. Whatever this says, **choosing a parent includes its children** (as the grid does), so a parent's count includes them. |
| `groups[].order` | `name` | `name`, `menu_order` (WooCommerce's own term order) or `count` (most products first). |
| `groups[].limit` | `null` | Show only the first N options; chosen options always stay. |
| `hide_empty_terms` | `false` | `false`: an option nobody can reach from here stays visible and muted (count 0). `true`: it is left out. |
| `max_values_per_group` | `10` | The request cap, see below. |
| `max_active_groups` | `6` | The request cap, see below. |
| `counts_cache_ttl` | `3600` | Seconds the counts are cached (`0` = not cached). |

The escape hatch is the `pkde_kit/shop_filters` filter: it receives the resolved array for a runtime tweak.

**A source that does not exist is skipped, never fatal.** A declared source that is not a product taxonomy (a typo, an attribute that was deleted) is left out of
the sidebar, an administrator sees a notice in wp-admin naming it, and the drift check fails the row "Shop filters: declared groups resolve". The drift check
also lists the declared groups (INFO), so a change between releases shows. **A new shop declares its filters** as a launch step (launch guide, "Shop filters").

## Counts: one definition of the result set

The number beside an option is exactly what the grid shows after choosing that option on its own in its group, on top of everything else applied: within a
group the choices are OR, between groups AND (the invariant, tested by the matrix below). To make that true by construction, the counts are computed from the
SAME query as the grid:

1. **The base set** is the main query's own arguments with only the sidebar's choices taken out: the archive (category, tag, brand, search), the price range,
   the `sage_view=sale|new` and `featured` views, catalogue visibility (hidden and search-only products), hiding out-of-stock products when WooCommerce is set to,
   published products only. One ID query. (WooCommerce's price clause only applies to the main query; it is switched on for this query with
   `woocommerce_enable_post_clause_filtering`, so **WooCommerce 9.9 or later**.)
2. **One query per active group** narrows the base set to that group's chosen terms (and their children).
3. **A group's counts** come from the products of the base set that match every OTHER active group, counted from the term relationships. A hierarchical
   taxonomy counts a product once for every ancestor of its terms. Variable products count once, under the parent; "in stock in that colour" at variation level is
   **out of scope** (a known limit).

**Queries.** On a cache miss: at most `2 x (active groups) + 2` product-side queries, however many groups are declared (plus one more per 5000 products in the base
set, the IN-list chunk size). On a cache hit: **none**. Term lookups for the lists and for validating the request are separate and unchanged (one per declared
group, one per active group). The cache key holds the canonical choices, the archive, the price and views, the hide-out-of-stock setting and a **catalogue version**
that moves when a product is saved, deleted or trashed, its stock or stock status changes, a product term or attribute is created, edited or deleted, or the
hide-out-of-stock setting changes; the cache is a transient, so it lives in the object cache where there is one. Sale-price schedules that start or end by cron
are not events: they show within `counts_cache_ttl`.

**Known limits (not proved).** A shop whose search is replaced by a search plugin such as Relevanssi: the counts on a search page are computed from WordPress's own
search for the same words, which can differ from the plugin's result list; check one search with a filter on staging. Variation-level stock (above). WooCommerce
before 9.9 (the price clause).

## Load: the request is checked before any product query

A request carrying `sage_filter_*` arguments is read by `ShopFilterRequest` in `pre_get_posts`, before the product query:

* **404, no product query and no filter UI** when a group has more than `max_values_per_group` values, more than `max_active_groups` groups are used, or every
  value given is unknown (an unknown group, a deleted term).
* **301 to the one canonical URL** when the request is valid but not canonical: values unsorted or repeated, empty values, an unknown value or group dropped while a
  valid choice remains, a non-numeric price. Groups come in the declared order, values sorted, so identical choices share one URL and one page-cache entry. (The
  sidebar form sends the boxes in the order of the list, so a multi-value submit is redirected once, to the sorted form. The price fields and the stock toggle may
  come anywhere.)
* **Apache** (pkde-deploy, the Query Flood Guard block in the shared `.htaccess`): 120 or more `sage_filter_` arguments in one query string are answered 410 before
  WordPress boots. A fixed number above any legal request (the defaults allow 6 x 10 = 60); a site that raises both caps so their product exceeds 120 edits the
  number. The pattern is anchored and uses an atomic group, so it cannot backtrack.
* **Crawl:** unchanged. Filtered URLs are `noindex, follow`, the form is a GET form with no filter links to follow, and there are no `robots.txt` Disallow lines.

The counts are also shown on a **product search** now, and so are the choices: a search used to ignore the sidebar's choices while the sidebar counted them.

## Tests and measurements

`tests/Stack/ShopFiltersMatrixTest.php` in the template runs the invariant over HTTP against a live template stack with WooCommerce and the catalogue of
`tests/Stack/seed-shop-filters.php` (simple and variable products, categories with children, brands, two attributes, several prices, out-of-stock, hidden,
search-only and catalogue-only products, a draft, sale, featured and new products): 28 combinations (a category page plus attributes plus a price, sale plus an
attribute, search plus a filter, pagination, sorting, hide-out-of-stock on and off, the declared stock toggle and a hierarchical category). For every option of every
view it compares the count with the grid total of the same view with that option alone. It is skipped without `PKDE_STACK_URL`, so CI ignores it.

## Archive size

Product archive queries use WooCommerce's `loop_shop_per_page` filter and are
set to 12 products per page. This provides complete four-row mobile, two-row
tablet, and four-row three-column desktop grids.

## Active filter chips, empty groups and noindex

* **Chips:** the sidebar starts with an "Active filters" card (`ShopFilters::activeChips()`): one removable chip per applied choice (Sale/New view, each chosen term, the price range), each linking to this same archive with just that choice taken out, plus "Clear all filters". The form posts back to the archive being viewed (the category or brand, else the shop).
* **Empty groups hidden:** a filter group none of whose options can be reached from the current view (and none selected) renders nothing, so there are no empty cards; chosen options are listed first.
* **Sale / New views:** `?sage_view=sale` (every product on sale) and `?sage_view=new` (the few most recent products, the ones the New badge is on) are the targets of the card badges on archives. The badge links carry `rel="nofollow"`: every product card links to the same two views, so without it crawlers spend budget on them. A robots.txt `Disallow` was not chosen on purpose: it would stop crawlers from reading the `noindex` below, and a URL already known would stay in the index without a snippet.
* **Faceted URLs are `noindex, follow`:** any request with a `sage_filter_*` choice, a price range or `sage_view` is served with `noindex, follow` (`App\is_faceted_request()`, the `wp_robots` filter in `app/woocommerce/filter-query.php`), so search engines do not index the combinations but still follow the product links.
