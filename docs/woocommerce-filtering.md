# WooCommerce filtering

## Current implementation

The shop sidebar is a custom, progressively enhanced GET form. It supports
multi-select product categories, tags, brands, and every registered global
product attribute. Changing a checkbox submits the form automatically; without
JavaScript, selecting controls and submitting the form still produces a normal
shareable URL.

The implementation is split across:

| File | Responsibility |
| --- | --- |
| `resources/views/partials/sidebar-shop.blade.php` | One shared filter form, price inputs, clear state, and all taxonomy filter groups. |
| `resources/views/partials/shop-term-filter.blade.php` | Checkbox group markup and dynamic count display. |
| `app/ShopFilters.php` | Allowed filter taxonomies, query parameter names, active values, and faceted counts. |
| `app/woocommerce/filter-query.php` | Migrates legacy filter URLs, applies selected terms to the main WooCommerce product query, and adds `?featured=1` support (see below). |
| `resources/js/app.js` | Auto-submits filter changes and omits blank price values. |

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

Legacy `filter_product_cat[]` and `filter_product_tag[]` array URLs are migrated
in `app/woocommerce/filter-query.php` before WooCommerce builds the main product query. New
theme code must only emit `sage_filter_*` taxonomy parameters.

## Dynamic counts

`App\ShopFilters::termCounts()` calculates a count for each available term using
all *other* active filter groups. For example, category counts respond to the
selected tags, brand, and attributes; tag counts respond to selected categories,
brand, and attributes. A zero count is muted but remains visible so shoppers can
understand why an option currently has no matching products.

This approach is appropriate for a skeleton/demo catalog. It runs a lightweight
ID-only product query for each filter group, followed by a term lookup. On a
large catalog, replace it with a dedicated indexed filtering solution rather
than adding more custom queries.

### Current implementation audit

The custom system has useful properties: all theme-owned parameters are
namespaced, term groups combine with `AND`, terms within one group use `IN`,
and it does not depend on JavaScript for the underlying request. Its limits are
equally important:

- Counts use other taxonomy selections and the archive context, but do **not**
  currently include the native `min_price`/`max_price` price state. Therefore a
  price change can make a displayed term count optimistic.
- Each rendered taxonomy group causes a separate product-ID query. The cost
  grows with both catalogue size and the number of filterable attributes.
- The form action is the Shop permalink. On a taxonomy archive this means a
  newly submitted filter returns to `/shop/` unless that archive term is also
  represented in the URL.
- Price parsing/filtering is WooCommerce-owned while taxonomy parsing is
  theme-owned. That boundary is intentional but makes complete faceting harder
  to maintain.

These are migration reasons, not reasons to layer a second custom count or
query engine onto this one.

## Adding a filterable taxonomy

Global product attributes are discovered automatically from WooCommerce. For a
new non-attribute product taxonomy, add it to `ShopFilters::taxonomies()`:

```php
'product_material' => __('Material', 'sage'),
```

The shared form, URL parameter, query handling, and dynamic counts will then be
provided automatically. Ensure the taxonomy is registered for the `product`
post type and uses term slugs suitable for URLs.

## Archive size

Product archive queries use WooCommerce's `loop_shop_per_page` filter and are
set to 12 products per page. This provides complete four-row mobile, two-row
tablet, and four-row three-column desktop grids.

## Active filter chips, empty groups and noindex

* **Chips:** the sidebar starts with an "Active filters" card (`ShopFilters::activeChips()`): one removable chip per applied choice (Sale/New view, each chosen term, the price range), each linking to this same archive with just that choice taken out, plus "Clear all filters". The form posts back to the archive being viewed (the category or brand, else the shop).
* **Empty groups hidden:** a filter group none of whose options can be reached from the current view (and none selected) renders nothing, so there are no empty cards; chosen options are listed first.
* **Sale / New views:** `?sage_view=sale` (every product on sale) and `?sage_view=new` (the few most recent products, the ones the New badge is on) are the targets of the card badges on archives. The badge links carry `rel="nofollow"`: every product card links to the same two views, so without it crawlers spend budget on them. A robots.txt `Disallow` was not chosen on purpose: it would stop crawlers from reading the `noindex` below, and a URL already known would stay in the index without a snippet.
* **Faceted URLs are `noindex, follow`:** any request with a `sage_filter_*` choice, a price range or `sage_view` is served with `noindex, follow` (`App\is_faceted_request()`, the `wp_robots` filter in `app/woocommerce/filter-query.php`), so search engines do not index the combinations but still follow the product links.
