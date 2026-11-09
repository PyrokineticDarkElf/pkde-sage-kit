# The product page: upsells, cross-sells, related, and the add-to-basket feedback

(Kit 2026.11.07.11, MGR-79.) The kit supplies all of this; a site sets variables and filters, it does not copy views.

## Sections under the product

Three sections, in this order, each a scroll-snap carousel of the **site's own card** (`woocommerce/content-product`, so a site's card override is used):

| Section | Default title | Products | Default count |
|---|---|---|---|
| Upsells | You may also like | the product's Upsells (Product data > Linked products) | 8 |
| Cross-sells | Goes well with | the product's Cross-sells (WooCommerce only prints these in the basket, where they stay) | 8 |
| Related | More from *<deepest category>* (Related products without a category) | WooCommerce's related query | 12 |

- A section prints **only when it has visible products**: no empty heading. A product shown in an earlier section is not repeated in a later one.
- **Cache-safe:** the ids come from the product's own links and WooCommerce's per-product related cache; nothing depends on the visitor (kit test; checked: a basket visitor's HTML is the same as an anonymous one's).
- The carousel is CSS scroll-snap (touch, wheel, keyboard, the browser's own snap) with previous / next buttons that appear when the list overflows and set `aria-disabled` at either end; no library. Reduced motion jumps instead of gliding. Images are `loading="lazy"`.
- Cards visible at once follow the shop grid: `--pkde-shop-columns` (4 from 1280px), `--pkde-shop-columns-tablet` (3 from 640px), `--pkde-shop-columns-phone` (2).

### Filters

| Filter | Default | Use |
|---|---|---|
| `pkde_kit/product_sections_enabled` | `true` | `false` leaves WooCommerce's own upsells and related output alone |
| `pkde_kit/product_sections` | `['upsells', 'cross-sells', 'related']` | the sections and their order; remove one to switch it off |
| `pkde_kit/product_section_title` | see above | `(string $title, string $key, WC_Product $product)` |
| `pkde_kit/product_section_count` | 8 / 8 / 12 | `(int $count, string $key, WC_Product $product)`; 0 switches it off |
| `pkde_kit/product_section_order` | upsells and cross-sells `['none', 'asc']` (as set in wp-admin), related `['rand', 'desc']` | `(array [orderby, order], string $key)`; any `wc_products_array_orderby` value |
| `pkde_kit/product_sections_dedupe` | `true` | `false` lets a product appear in two sections |
| `pkde_kit/related_load_more` | `0` | `(int $perClick, WC_Product $product)`; above 0 adds a "Show more" button to the **related** section |

"Show more" asks `?wc-ajax=pkde_related` (a GET, the same for every visitor, never page-cached) and gets the next products **as the site's own card HTML**, not JSON. (The WooCommerce Store API returns product data, not markup, so it could not draw a site's own card; the endpoint does, and only for related products.) Upsells and cross-sells are short hand-picked lists and have no button.

## Headings on the product page

Every H2 on the product page (the tabs' Description, Additional information and Reviews, WooCommerce's own Related/Upsells heading when the kit sections are off, and the sections' titles) uses one scale: `--pkde-product-h2-font` (the display font), `--pkde-product-h2-size` (1.5rem), `--pkde-product-h2-weight` (`--pkde-shop-heading-weight`, 600), `--pkde-product-h2-leading` (1.25), `--pkde-product-h2-tracking` (normal). Tailwind's reset made a bare H2 body-sized, so Description, Additional information and Related products were 16px body text beside a 24px Reviews title. A site points the variables at its heading scale, for example `--pkde-product-h2-size: clamp(1.5rem, 3vw, 2.25rem)`.

## Add-to-basket feedback

`resources/js/add-to-basket.js`, `resources/css/add-to-basket.css`, `app/woocommerce/add-to-basket.php`.

- **Busy:** a button that is working has `aria-busy="true"` and `aria-disabled="true"` (not `disabled`: a disabled submit button is left out of the form data and WooCommerce would lose the product id), shows a spinner, and a second click is swallowed before WooCommerce sees it. A polite live region says "Adding to basket…".
- **Added:** the button shows a check mark (and, when it is plain text, the word "Added") for three seconds, WooCommerce's "View basket" link appears beside it, and the live region says "*Name* added to basket." A button nothing answers for 20 seconds is released and the live region says so.
- **Where:** the product cards' AJAX buttons (WooCommerce's `adding_to_cart` and `added_to_cart` events) and the product page's form.
- **AJAX on the product page:** a plain simple-product form (quantity and the button, nothing else) is added without a reload through WooCommerce's own `add_to_cart` endpoint; the header count updates from the returned fragments. Variable, grouped and add-on forms submit normally (their busy state shows until the page reloads), and any failed request falls back to the ordinary submit, so nothing is lost. On when the shop's setting "Enable AJAX add to cart buttons on archives" is on and "Redirect to the basket page after successful addition" is off; `pkde_kit/ajax_add_to_cart_single` (bool, `true`) turns it off.
- **Look:** `--pkde-atc-spinner-size` (1em), `--pkde-atc-spinner-color` (currentColor), `--pkde-atc-busy-opacity` (0.75), `--pkde-atc-added-color` (currentColor). Reduced motion: the spinner is a still ring.
- The words and endpoints come from one region the footer prints (`data-pkde-atc-status`), the same for every visitor.
