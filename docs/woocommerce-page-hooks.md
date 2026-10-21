# WooCommerce page hooks

This theme renders WooCommerce's classic templates through `woocommerce_content()`
inside Acorn Blade layouts. Blade owns the page shell; WooCommerce retains the
purchase and loop internals.

## Theme-owned decisions

| Hook / callback | Theme behavior |
| --- | --- |
| `woocommerce_before_main_content` / `woocommerce_output_content_wrapper` | Removed; Blade supplies the layout container. |
| `woocommerce_after_main_content` / `woocommerce_output_content_wrapper_end` | Removed with the opening wrapper. |
| `woocommerce_before_main_content` / `woocommerce_breadcrumb` | Removed; the shared Blade breadcrumb is canonical. |
| `woocommerce_before_shop_loop` / `woocommerce_output_all_notices` | Removed; notices render directly beneath archive headers. |
| `woocommerce_before_single_product` / `woocommerce_output_all_notices` | Removed; notices render beneath the product breadcrumb. |
| `woocommerce_single_product_summary` / `woocommerce_template_single_meta` | Replaced by the `term-pills` partial. |
| `loop_shop_per_page` | Set to 12 products per archive page. |

`resources/views/partials/woocommerce-notices.blade.php` calls
`wc_print_notices()` exactly once per archive or product request.

## Retained hook regions

- Archives retain `woocommerce_before_shop_loop` for result count and sorting,
  and `woocommerce_after_shop_loop` for pagination.
- Product pages retain the default gallery/sale hook region, product summary
  priorities (title, rating, price, excerpt, add-to-cart), then tabs, upsells,
  and related products.
- Cart, checkout, and account hooks are intentionally untouched because they
  are payment and extension sensitive. Their notices receive global styling.

The theme replaces WooCommerce's default product-card template. It calls
`woocommerce_template_loop_add_to_cart()` directly; do not re-enable the
default per-card link/image callbacks without restructuring the Blade card,
because they would create nested links.

## Importer extensions

CSV importer hooks are isolated in `app/woocommerce/importer.php`. The current
extension exposes and automatically maps a `Slug` column. See
[`woocommerce-importer.md`](woocommerce-importer.md) for its contract and
extension guidance.

References: [WooCommerce hooks](https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/) and [template structure](https://woocommerce.com/document/template-structure./).
