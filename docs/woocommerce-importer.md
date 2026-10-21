# WooCommerce CSV importer extensions

## Product slug column

The theme extends WooCommerce's native product CSV importer with a `Slug`
column. It is registered in
`app/woocommerce/importer.php`, which is included by `app/woocommerce.php`
only when WooCommerce is active.

This adds two complementary mappings:

| WooCommerce hook | Effect |
| --- | --- |
| `woocommerce_csv_product_import_mapping_options` | Adds **Slug** to the importer’s manual column-mapping choices. |
| `woocommerce_csv_product_import_mapping_default_columns` | Automatically maps a CSV header named `Slug` to WooCommerce's internal `slug` field. |

Use an exact `Slug` header in CSV files:

```csv
Name,Slug,Regular price
Example product,example-product,29.00
```

The WooCommerce importer remains responsible for validation, updates, and
conflict handling. The theme only supplies the column mapping; it does not
replace the importer or alter product slugs after import.

## Adding another mapping

Keep importer-only hooks in `app/woocommerce/importer.php`. For each new CSV
field, add both the selectable mapping option and its expected-header default
mapping, using WooCommerce's internal product property key. Do not place CSV
import hooks in `app/woocommerce.php`; that file is reserved for shared theme
support and storefront behavior.

Before adding a mapping, confirm that the native WooCommerce importer supports
the target property and test both automatic and manually selected mapping with
a small CSV in a non-production environment.
