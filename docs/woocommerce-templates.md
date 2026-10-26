# WooCommerce templates

For what `bin/setup` seeds into a store when you opt into WooCommerce (catalogue, attributes,
brands, gallery images), see [the bootstrapper's e-commerce doc](../../../../../bin/docs/ecommerce.md).
This one covers what the *theme* has to render it.

**What you get without writing any new templates:** Cart, Checkout, and the Login/Register/
Account pages. WooCommerce creates these as ordinary WordPress Pages — Cart/Checkout with its own
Cart/Checkout blocks embedded, My Account as a `[woocommerce_my_account]` shortcode — so they
already render correctly through the theme's existing generic page template, styled by WC
Blocks' own default (reasonably neutral) stylesheet. Upsells (product page) and cross-sells (cart
page) also need nothing extra — `woocommerce_upsell_display`/`woocommerce_cross_sell_display` are
on by default and just needed seed data with something to show.

**What did need new templates:**

- `archive-product.blade.php`: **every** product archive: the shop, product categories, tags and brands.
  `app/woocommerce/template-resolution.php` routes the three taxonomies to it through one
  `taxonomy_template_hierarchy` filter (priority 9, before Acorn); there are no separate
  `taxonomy-product_*` templates any more (a more specific `taxonomy-product_cat-<slug>.blade.php` would still
  win). The plain shop is the Shop page's own content (`App\View\Composers\ProductArchive`): the **Shop
  products** block (`sage/shop-products`) prints the live archive (filters, sorting, pages) where it sits in the
  page, and the archive is appended after the content when the block is missing, so the shop always lists
  products. Every other archive uses `partials/shop-archive.blade.php` (title, notices) and
  `partials/shop-archive-body.blade.php` (filters + `woocommerce_content()`). `template-brands.blade.php` is a
  **Brands directory page** (`/brands/`: a card grid of every brand, image + name + product count, linking to its
  archive). All product archives share one **filter sidebar**,
  `partials/sidebar-shop.blade.php` — see [WooCommerce filtering](woocommerce-filtering.md) for
  how that's actually built (a custom faceted filter, not WooCommerce's bundled widgets).
- `single-product.blade.php` — category/tag pills under the title (replacing WC's plain
  "Category: Tags:" text meta line, via `partials/term-pills.blade.php`), the attributes table and
  gallery slideshow both come from WooCommerce's own default rendering once the seed data above
  exists.
- `woocommerce/content-product.blade.php`, loaded through the standard WooCommerce override
  `woocommerce/content-product.php` (with the `@version` line and an `is_visible()` guard), is the
  per-product **loop card** (Shop grid, archives, related products, upsells, product blocks): Sale/New
  badges (links to `?sage_view=sale|new` on archives, plain labels on curated rows), image, rating, name, price,
  add-to-cart. It fires WooCommerce's five card hooks in their usual places so plugins can still add to it;
  `app/woocommerce/display.php` removes WooCommerce's own callbacks on those hooks, apart from add-to-cart.

**No theme overrides for settings WooCommerce already has:** products per row and rows per page (Appearance >
Customize > WooCommerce > Product Catalog; the theme only sets the defaults, 3 per row and 4 rows, through
`add_theme_support('woocommerce', ['product_grid' => ...])`), what the shop and category pages show, related
products, the "Add to basket" text. Change them there, or in the site language's translations.

Each archive/taxonomy template is a thin wrapper (`@extends('layouts.app')` + breadcrumbs +
`woocommerce_content()`) — WooCommerce's own function for exactly this purpose, so the real
product loop/pagination is WooCommerce's actual rendering, not a reimplementation.
`app/woocommerce.php` declares `add_theme_support('woocommerce')` (plus the gallery
zoom/lightbox/slider supports), and `resources/css/app.css`'s `@layer components` block has a
Tailwind pass targeting WooCommerce's predictable classes since `woocommerce_enqueue_styles` is
filtered off so WooCommerce's own bundled classic CSS doesn't fight it.

**Two non-obvious things worth knowing if you touch this code:**

1. `WC_Template_Loader::template_loader()` resolves the Shop/category/single-product templates by
   calling WordPress core's `locate_template()` directly — a raw filesystem scan for a literal
   `.php` file — which never finds Acorn's compiled Blade views, so left alone it always falls
   back to WooCommerce's own bundled default templates regardless of the `.blade.php` files above.
   `app/woocommerce/template-resolution.php` removes that filter (on `init`, at a later priority
   than WooCommerce's own registration — both are on `init`) so Acorn's own, earlier-running
   `archive_template_hierarchy`/`single_template_hierarchy` resolution — which already correctly
   finds these Blade views — isn't overridden after the fact. **`WC_Brands` (the brand taxonomy)
   registers a second, separate `template_include` filter of its own** — an instance method on
   `$GLOBALS['WC_Brands']`, not the static `WC_Template_Loader` one above, so removing that one
   doesn't touch this one; it does the exact same raw-`locate_template()`-then-bundled-fallback
   thing, just for brand pages. `app/woocommerce/template-resolution.php` removes both.
2. **Don't mix Blade's `@php(expr)` inline form with a `@php ... @endphp` block form in the same
   view file.** Confirmed directly against Laravel's Blade compiler: if a parenthetical
   `@php(...)` appears anywhere *before* a bare `@php`/`@endphp` block in the same file, the
   compiler corrupts both — the inline form loses its closing `?>`, and the block's opening `@php`
   is left completely unconverted while its `@endphp` still becomes `?>`, producing a PHP syntax
   error at render time. (This is what silently broke `taxonomy-product_brand.blade.php` during
   development — WooCommerce's `WC_Brands` template bypass above was masking it, serving its own
   unstyled fallback instead of ever hitting the syntax error directly.) If a view needs both a
   one-liner and a multi-statement PHP block, either order the block first, or just use a raw
   `<?php ... ?>` tag for the one-liner instead of the `@php(...)` shorthand — that sidesteps the
   directive parser entirely regardless of ordering.

**One known limitation, not a bug:** the product gallery's thumbnail-strip/zoom/slider
interaction is WooCommerce's own bundled `wc-single-product` script, which needs real
layout/paint to activate — nothing to build here once real gallery images exist (confirmed via
the seeded data above: correct markup, correct `wc_single_product_params` config, scripts loading
correctly). It just can't be visually demonstrated in a background browser pane during
development, the same limitation as the ToC scroll-spy noted in
[single-post-layout.md](single-post-layout.md).

## Shop polish: stars, equal cards, category accordion, basket and checkout (`resources/css/shop-pages.css`)

All CSS, imported by `kit.css`; the tokens are the kit's own (neutral, brand and their `-fg` text colours), so a site's palette carries through.

* **Rating stars.** WooCommerce prints "Rated 5.00 out of 5" and relies on its star font, which the kit does not load. `.star-rating` is now five mask-drawn stars (`--pkde-star` sets the size), amber-700 on a neutral-200 track (5.0:1 against white), filled to the rating by WooCommerce's own inline width. The element keeps `role="img"` and its `aria-label`; the words are only hidden visually.
* **Equal-height product cards.** The card's text block grows and the button area sits at the bottom of every card in a row; "View cart" (`a.added_to_cart`) after an AJAX add is a text link next to the button, not a second black pill.
* **Category description accordion.** `app/woocommerce/display.php` prints a product category's description as a closed `<details class="pkde-term-description">` ("About {category}") above the products, first page only; the text is WooCommerce's own, unchanged.
* **Basket, checkout and account** (classic shortcode pages, scoped to the `woocommerce-cart`, `woocommerce-checkout` and `woocommerce-account` body classes, `:where()` so a site overrides with one selector): items left and a sticky totals card right with cross-sells under the items, a 5.5rem thumbnail, coupon and update on one row, the order review beside the billing details, a card list on phones; shipping methods are bordered selectable rows (the native radio stays, the whole row is the label) on both the basket and the checkout; "Ship to a different address?" is styled as a section heading. These pages get the page measure instead of the 48rem reading column (the column's own `px-6` keeps the edges on the header's). Block-based Cart and Checkout (WooCommerce's default for a new store) have their own markup and are not touched.

Social profiles: the footer prints round icon links through `pkde-kit::partials.social-links` for the options `client_facebook`, `client_instagram`, `client_linkedin`, `client_x` and `client_youtube` (full URLs; set with `wp option update`). Nothing is printed when none is set. The list carries `aria-label="Social media"` and the class `pkde-social-links`, and its links are `display: grid` on purpose (44px round targets): a site footer rule such as `.content-info li a { display: inline-block }` would break them, so scope it, for example `.content-info li:not(.pkde-social-links li) a { ... }`, or restyle through the `class` argument.
