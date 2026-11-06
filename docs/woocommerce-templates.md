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
  products. Every other archive uses `partials/shop-archive.blade.php` (title, notices, the term's description in a closed toggle) and
  `partials/shop-archive-body.blade.php` (filters + `woocommerce_content()`); `partials/shop-page.blade.php` is the body of the site's
  `archive-product` view (see [The standard shop archive](#the-standard-shop-archive)). `template-brands.blade.php` is a
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

## Search: Relevanssi with live results (optional)

tcs and bb carried the same product search, so it lives in the kit. Everything is inert until a site asks for it:

1. Require the plugins: `wpackagist-plugin/relevanssi` (^4.28) and `wpackagist-plugin/relevanssi-live-ajax-search` (^2.6). **Both sites use the FREE editions** (tcs and bb); Relevanssi Premium works the same. Nothing in the kit needs Premium.
2. Run `wp eval-file vendor/pkde/sage-kit/bin/relevanssi-settings.php` once per environment (after a deploy): products only, the SKU and every product taxonomy indexed, AND between words with an OR fallback, partial words, titles weighing five times the body, no excerpts, searches logged without IP addresses, hidden and noindex products excluded; it rebuilds the index.
3. Print the form where you want it: `@include('pkde-kit::partials.product-search', ['id' => 'site-search'])` (the header bar), or use the header panel below. `$id` keeps two copies on a page apart.

What the kit does for you (`app/search.php`, `resources/css/live-search.css`): Relevanssi answers the query itself, so WooCommerce's price, popularity and rating sort choices are handed over as the meta fields they sort by; the live results are the kit's Blade view (`partials/live-search-results`: product image, title and price rows, arrow-key navigation, a last row that opens the full results) through the plugin's template filter, so no theme file is needed (a theme's own `relevanssi-live-ajax-search/search-results.php` still wins); the plugin's own stylesheet is dequeued; six results by default (`apply_filters('pkde_kit/live_search_results', 6)`); a product search (`?s=...&post_type=product`) lists products in the shop's template with its filters and sorting. A product search results page is titled "Search Results for ...", not "Shop" (the page-header composer now checks the search before the archive).

## Header options (opt-in)

* **Search icon and panel.** `add_filter('pkde_kit/header_search', '__return_true')` (in the theme's `app/filters.php`) turns on a search icon in the header bar that opens a panel under it, with the product search and its live results, instead of an inline field in a menu that has no room for one. The menu panel closes first, the cursor goes into the field, Escape closes the panel and returns focus to the icon. The template's header includes `pkde-kit::partials.header-search` for the icon and the panel; a site with its own header includes the same partial twice (`['part' => 'button']` and `['part' => 'panel']`).
* **Mobile-menu breakpoint.** The menu leaves the bar for the menu button below Tailwind's `sm` breakpoint (640px) by default. A site with a long menu moves it: `add_filter('pkde_kit/nav_breakpoint', fn () => 'xl')` (`sm`, `md`, `lg`, `xl` or `2xl`; bb needs `xl`, 1280px). The classes come from `App\nav_classes()`, written out as literal strings so Tailwind generates them.

## The standard shop archive

One layout for every site (it was tcs's, MGR-77): the title, the notices, the term's description in a closed "Read more about X" toggle, then the filters in a
column beside the results (stacked below 1024px). A site does not copy the partials; it changes the look with variables and the details with hooks, and
its `archive-product.blade.php` is three lines:

```blade
@extends('layouts.app')
@section('content')
  @include('pkde-kit::partials.shop-page')
@endsection
```

Markup (BEM, so a site targets it without the `woocommerce` class, which the list deliberately does not carry, TCS-72): `.shop-archive`,
`.shop-archive__filters`, `.shop-archive__filters-title`, `.shop-archive__results`, and the toggle `details.pkde-term-description`. The styles are
`resources/css/shop-pages.css`.

**Variables** (set on `:root`; each has a kit-token fallback, so a site that sets none still looks right):

| Variable | What it colours or sizes | Default |
|---|---|---|
| `--pkde-shop-line` | rules, borders of the toggle, panels, sort and pagination | `--color-neutral-200` |
| `--pkde-shop-muted` | the term text and the result count | `--color-neutral-600` |
| `--pkde-shop-ink` | text of the sort and pagination, the current page | `--color-neutral-950` |
| `--pkde-shop-accent` | the + / - mark and the filters heading | `--color-brand-700` |
| `--pkde-shop-surface` | background of the sort, pills and filter panels | `--color-white` |
| `--pkde-shop-radius` | sort, pagination and view buttons | `999px` |
| `--pkde-shop-panel-radius` | the filter panels | `20px` |
| `--pkde-shop-filters-width` | the filter column from 1024px | `15rem` |
| `--pkde-shop-sticky-top` | where the filter column sticks (the sticky header's height plus a gap) | `5.5rem` |
| `--pkde-shop-heading-weight` | headings inside a term description (a light brand sets 300) | `600` |
| `--pkde-shop-columns` | product grid columns from 1280px (the filter column beside the results) | `4` |
| `--pkde-shop-columns-tablet` | product grid columns from 640px to 1279px | `3` |
| `--pkde-shop-columns-phone` | product grid columns below 640px | `2` |
| `--pkde-shop-select-inset` | the sort select's chevron, distance from its right edge | `1rem` |
| `--pkde-shop-select-chevron` | the sort select's chevron image (a CSS `url(...)`, e.g. in the brand colour) | a thin grey chevron |
| `--pkde-shop-pad-top` | space above the filters and the results | `1rem` |
| `--pkde-shop-gap` | column gap between products (the same on every site; a site that wants more sets it) | `1.5rem` |

**Hooks** (a site adds them in its own code):

| Hook | Kind | Effect |
|---|---|---|
| `pkde_kit/shop_term_toggle` (`bool $show`, `?WP_Term $term`) | filter | `false` hides the description toggle (default `true`) |
| `pkde_kit/shop_filters_title` (`string $title`) | filter | the heading over the filters; `''` removes it (default "Filter your search") |
| `pkde_kit/shop_before_results`, `pkde_kit/shop_after_results` | action | print inside the results column, before and after the toolbar and products |
| `pkde_kit/shop_page_header` (`bool $show`) | filter | `false` leaves the page header off the Shop page (default `true`) |

The toggle is printed once, above the layout; the copy WooCommerce would print inside the product list is removed there. A site view that calls
`woocommerce_content()` without these partials still gets the toggle inside the list (`App\term_toggle()`).

## Sale and New badges, and the category tile image (MGR-77)

`partials/product-badges` is the one place the Sale and New badges are drawn: the kit's card includes it, and a site's own card includes it too
(`@include('pkde-kit::partials.product-badges', ['product' => $product])` inside the picture's box, which is `position: relative`; `'links' => true|false` to force
links, otherwise archives and searches link to `?sage_view=sale|new` and curated rows do not). The product page can print them in a `.product-badges` wrapper.

* **When New shows:** the N newest products (4; `pkde_kit/recent_counts` changes the counts per post type) or, with `pkde_kit/new_badge_days` above 0 (filter, `int $days`, `string $postType`), the products published within that many days. The `?sage_view=new` list follows the same rule. Sale: the product is on sale.
* **Markup and filter:** `.pkde-badge.pkde-badge--sale|new`; `pkde_kit/product_badges` (list of badges, product) adds, removes or reorders a badge.
* **Look, on `:root`** (each with a kit-token fallback): `--pkde-badge-sale-bg`, `--pkde-badge-sale-fg`, `--pkde-badge-new-bg`, `--pkde-badge-new-fg`, `--pkde-badge-radius` (999px), `--pkde-badge-size` (0.625rem), `--pkde-badge-weight` (600), `--pkde-badge-tracking` (0.14em), `--pkde-badge-padding`, `--pkde-badge-top`, `--pkde-badge-inset`.
* **Category tile image:** `\App\category_tile_image($attachmentId, $url, $alt, $class, $sizes)` renders a tile photo through the media library (width, height, srcset, sizes; an attachment id, or the URL of an original or a resized upload) and falls back to a plain lazy `<img>`; pair it with `\App\category_menu_image($termId)` for the most popular product's photograph. The kit's own tiles are unchanged; a site's own tile design calls the helper.

**The grid has a fixed column count** (kit 2026.11.06.6 or later): 4 from 1280px, 3 from 640px, 2 below, the same on every site. It no longer follows the results width, the gap, the root font size or the filter column, and WooCommerce's own "products per row" class (`columns-4`) and a site's rules for it do not change it. At 1440 a card is 216px wide (4 columns, 24px gap, the 240px filter column), at 1024 it is 211px (3 columns). A site that wants another count sets `--pkde-shop-columns`.

**The grid / list toggle follows the site's brand** (kit 2026.11.07.5): `--pkde-view-toggle-border` (default `--pkde-shop-line`), `--pkde-view-toggle-color` (the idle icon, neutral-700), `--pkde-view-toggle-hover` (the hovered border, brand-600), `--pkde-view-toggle-active-bg` (the pressed button, brand-900) and `--pkde-view-toggle-active-fg` (its icon: `--color-brand-900-fg`, white without it). The icons draw in `currentColor`.

**Where the star rating sits in the card** (kit 2026.11.07.6): `pkde_kit/card_rating_position` (filter, string): `above_button` (default: after the title, the price and the short description, directly above the action row), `below_title` (after the title, before the price) or `above_title` (before the title, the older look). The rating is printed in that place in the markup, so the reading and tab order match what the eye sees; a site does not reorder it with CSS `order`. In the `above_button` position the stars and the button stay together at the card's bottom (`.pkde-product-card__rating--above-button`); a card without a rating keeps the button there on its own. A site that copies the card prints the rating where it wants and needs no option.
