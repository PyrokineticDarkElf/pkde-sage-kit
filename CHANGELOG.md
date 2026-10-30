# Changelog

## 2026.10.31.1

- **Block style variations no longer fatal with block assets loaded on demand (el, EL-13).** A page with an `is-style-*` block (the outline button) raised "`block-style-variation-styles` was enqueued with dependencies that are not registered: `global-styles`" (an error page in development; in production the variation CSS could drop). Cause: with on-demand assets WordPress renders the content before `wp_enqueue_scripts`, so the variation handle is registered, and core enqueues it at `wp_enqueue_scripts` priority 1; its dependency `global-styles` is, for a classic theme, registered only in `wp_footer` (priority 1) and hoisted into the head, so in the head the dependency does not exist yet. New `app/block-assets.php` moves that one enqueue to `wp_footer` priority 2 for a classic theme loading on demand, so it runs after `global-styles` exists and its CSS is hoisted into the head with the other late styles, in the same cascade position (verified: global styles then the variation styles, both in `<head>`; the page was a 500, now 200). Registering `global-styles` early is not a fix (the head would print the empty handle as a dependency and the real global styles would never print), and `should_load_separate_core_block_assets` stays untouched: the layout-shift fix (TCS-87) is kept.
- Tests for the rule and the guard that nothing turns separate block assets off again. The template's block demo now carries a filled and an outline button; if `/block-demo/` returns 500 the clash is back.
**Adopt:** bump the kit. el deletes its `should_load_separate_core_block_assets => false` workaround in `ThemeServiceProvider` (and any early `global-styles` registration).

## 2026.10.31

- **The page header is a brand-coloured band by default, themed with variables (owner, from bb's site.css; MGR page-header).** `partials/page-header.blade.php` no longer hard-codes `bg-neutral-50 border-neutral-200`, `text-neutral-950` and `font-semibold`; new `resources/css/page-header.css` (imported by `kit.css`) styles `.page-header` from CSS custom properties with palette-derived defaults:

  | Variable | Default |
  |---|---|
  | `--pkde-page-header-bg` | `var(--color-brand-100)` (the palette's light brand tint; neutral-50 when the palette has no brand) |
  | `--pkde-page-header-border` | `var(--color-brand-200)` |
  | `--pkde-page-header-title` | `var(--color-neutral-950)` (ink) |
  | `--pkde-page-header-crumb` | `var(--color-neutral-700)` |
  | `--pkde-page-header-title-font` | the display font |
  | `--pkde-page-header-title-weight` | `600` (a one-weight serif sets `400`) |
  | `--pkde-page-header-title-tracking` | `-0.04em` |

  Every site gets a brand-coloured band under the header, on pages, archives, the shop, the cart and checkout (everything that includes `pkde-kit::partials.page-header`). A site changes the look by setting variables in its own CSS, never by rewriting rules.
- **Contrast is checked (`npm run theme:check`, also on generate):** for every brand colour, the default title (neutral-950) and breadcrumbs (neutral-700) must reach 4.5:1 on its 100 step; a failure names the band and the fix. Verified for light and dark brand colours in a test.
- Layout fingerprint of the template before and after: identical (10 page/width combinations, 1512 elements): the change is colour only.
**Adopt:** bump the kit. DELETE site CSS that only restyles the band or the title (bb `site.css` 45-46: `.page-header h1 { font-family; font-weight: 400; letter-spacing; color }` and `.page-header { background; border-color }`; tcs `site.css` ~593-616), then set the variables only where the default does not suit, for bb: `:root { --pkde-page-header-title-weight: 400; --pkde-page-header-title-tracking: -0.02em; --pkde-page-header-bg: var(--wp--preset--color--sage-200); --pkde-page-header-border: var(--wp--preset--color--sage-200); --pkde-page-header-title: var(--wp--preset--color--slate-800); }`. A site's own copy of `page-header.blade.php` keeps its markup; take the kit's classes out of it if it should follow the variables.

## 2026.10.30.2

- **The block editor canvas shows the page at its real width (owner: the editor area was far narrower than the front end).** Two causes, both in the editor only:
  1. The theme's `editor.css` never imported the kit, so the Section block's measure and gutter rules did not exist in the canvas: a Section's content was sized by `max-width: var(--wp--style--global--content-size)`, the 48rem reading width, where the page prints the header's 77rem. The rules moved into `resources/css/page-measure.css` (imported by `kit.css`, unchanged on the front end) and the new `resources/css/editor-width.css` imports the same file for the canvas.
  2. A page that uses a wide page template (the template's "Wide Content", which prints its text at the whole page measure through `content-page-wide`) was edited at 48rem. `kit-editor.js` marks the canvas root `.is-wide-page` for those templates (the list is `pkdeWideTemplates` in the editor settings, filter `pkde_kit/wide_page_templates`, default `template-wide-content.blade.php`) and `editor-width.css` sets the root's content width to the page measure (WordPress prints the root's width as a literal `48rem`, so the root needs its own rule), with the gutter on a canvas narrower than the measure.
  Verified in the real editor (local): a wide-template page's root paragraph and a Section's content both at the page measure with the 24px gutter (24..1136 on a 1160px canvas); a default page keeps its 48rem reading column for root text and the measure for a Section's content.
**Adopt:** bump the kit and add ONE line to the theme's `resources/css/editor.css`, below the theme import and above `site.css`: `@import "../../vendor/pkde/sage-kit/resources/css/editor-width.css";`. A site with its own page template that uses `content-page-wide` adds its file name: `add_filter('pkde_kit/wide_page_templates', fn ($t) => [...$t, 'template-landing.blade.php']);`.

## 2026.10.30.1

- **Views grouped by use, and default archive templates (owner request via tcs, TPL-140).** New `app/template-folders.php`: WordPress's template hierarchy names (`category.php`, `taxonomy-product_cat.php`, `archive-product.php` ...) get the folder file added right after the flat one (`blog/category.php`, `shop/taxonomy-product_cat.php`, `pages/page.php`), so the template can keep its default views in `resources/views/blog/`, `shop/` and `pages/`, and a site's flat view always wins. Page templates chosen in the editor, custom post types and other taxonomies stay flat. `docs/view-folders.md` has the table and the rules.
- New partial `pkde-kit::partials.term-description` (a category or tag description under the title, through `wp_kses_post`), used by the template's new `blog/category` and `blog/tag`.
- The product archive composer and the product search/taxonomy routing know the `shop.archive-product` view name and the `shop/archive-product.php` candidate.
- Tests for the folder mapping (`category`, `category-news`, `taxonomy-product_cat-shoes`, ... and what stays flat) and the order (flat first).
**Adopt:** bump the kit. A site keeps working unchanged (flat views win). To use the default archive views, take the template's `blog/`, `shop/` and `pages/` views (or leave yours); to override one archive add one flat file.

## 2026.10.30

- **The page gutter moves from padding on every child into the content grid's tracks (bp, MGR-62 follow-up).** Kit 2026.10.29 padded each direct child of `.content-grid` by `--page-gutter`: text and images lined up with the header, but anything PAINTED (a border, a background, a card, a stat strip) was 24px wider each side than the text, and an absolutely positioned child (`md:absolute md:inset-x-8` inside a `relative` block) was measured from the padded box, so a card sat 8px from the image edge instead of 32px. Now the grid defines `--content-grid-measure` (the header's content width, `--container-content` less two gutters, or the container less two gutters on a narrow screen), the wide track is that measure, the content track is `--content-grid-width` (48rem, never wider than the measure), and the child padding rule is gone. A child's box is its track: text, images, borders and backgrounds all start where the header's content does (104..1336 at 1440, 24..366 at 390). `content-page-wide` and the cart/checkout/account pages set `--content-grid-width: var(--content-grid-measure)`.
- **Visible changes by design:** wide/painted blocks are 24px narrower each side (they used to reach into the gutter); the prose text column is the full 48rem (it was 45rem: 48rem less the child padding). A site that patched this locally (bp's `body.home` CSS, a `margin-inline` rule on `.content-grid > *`) deletes the patch; a block with its own `px-*` keeps it.
- Test: the grid defines the measure token and no longer pads its children.
**Adopt:** bump the kit; check with `fingerprint.sh edges` (pkde-deploy 2026.10.30 also checks the painted box of blocks that span the measure).

## 2026.10.29.1

ADOPT ALL audit-fix batch (MGR-67, TPL-138). **Adopt:** bump the kit; a site that calls `wp_oembed_get()` in its own views switches to `\App\cached_oembed()`.

- **`\App\cached_oembed($url, $args)` (js audit):** `wp_oembed_get()` fetches the provider on every call; this keeps the HTML 12 hours and a failed fetch 10 minutes (so a down provider is not retried on every page view), and never fetches a non-http(s) URL.
- **Shop page content respects status and passwords (tcs audit):** the plain shop page prints the Shop page's content only when it is published (or the viewer may read it) and not password-protected; otherwise the standard archive layout is used.
- **Block `render.php` files exit when requested directly (el audit):** an `ABSPATH` guard in every block (the deploy tools also deny theme PHP at the server since 2026.10.29).
- Triaged and not changed: `TableOfContents` has no final `wp_kses_post` pass (bp hardening note). The content is the output of `the_content`, already filtered on save for users without `unfiltered_html` (which only Administrators have since platform 2026.10.29); a kses pass over the output would strip the embeds (iframes) and forms that Administrators legitimately put in posts. Accepted, recorded in the template's ADR. The live search's result count is fixed by the kit's filter (`pkde_kit/live_search_results`), not read from the request.

## 2026.10.29

- **One width system (MGR-62; owner: the 404 and most sections/rows were narrower than the header on nearly every site, and each site patched it differently).** Root causes found and fixed in the kit:
  1. **Page content grid used a 90% cap.** `.content-grid` (every page's `the_content()`) capped its tracks at `min(<ideal>, 90%)` AND padded each child by 1.5rem, so text started 19px to 75px inside the header's content edge between 390px and 1400px. Now `min(<ideal>, 100%)`; the child's `padding-inline: var(--page-gutter, 1.5rem)` is the only gutter, the header's. `content-page-wide` uses `min(var(--container-content, 80rem), 100%)`.
  2. **Section blocks sized by theme.json `contentSize` (48rem) and padded by the `gutter` preset.** The Section's content measure was opt-in (`--pkde-section-measure`). It is now the default: `calc(var(--container-content, 80rem) - 2 * var(--page-gutter, 1.5rem))` for direct children and wide children, and the Section's own `--wp--preset--spacing--gutter` is `var(--page-gutter, 1.5rem)`. Set `--pkde-section-measure` to any length to override (e.g. `var(--wp--style--global--content-size)` for the old reading width).
  A test fails if the 90% cap or the opt-in fallback comes back. `docs/native-blocks.md` "One page measure" rewritten. **Adopt:** bump the kit and the template's views/theme.json (see the template entry); delete local width hacks (a `.site-wrap`, `.rp-container`, a Section override, a `--pkde-section-measure` line that only restates the default). Check with `fingerprint.sh edges` (pkde-deploy). Visible change: a Section's content is now as wide as the header's, and page text lines up with it at tablet and phone widths.

## 2026.10.28

- **Fleet-wide layout-shift fix (tcs, TCS-87):** removed `add_filter('should_load_separate_core_block_assets', '__return_false')` from `app/setup.php`. On WordPress 6.9+ it disabled the on-demand block styles of classic themes, so every block's CSS loaded and the block-supports CSS printed after the footer: cumulative layout shift 0.36 to 0.42 on tcs staging, 0 after the removal, with computed styles identical on seven page types and about 20 KB less `wp-block-library`. A test fails if the filter comes back. **Adopt:** bump the kit; a site whose own `app/setup.php` still has the line (copied from the Sage starter) deletes it.

## 2026.10.27

- Phone numbers (TPL-77, MGR-35): `resources/js/phone.js`, imported by `kit.js`: the country picker (intl-tel-input 29.5.4, bundled and self-hosted, utils loaded by Vite, no CDN) on `.wpcf7 input[type="tel"]`, a hidden `<field>-country` the platform's `phone-e164` module reads, the visitor's country from Cloudflare's same-origin `/cdn-cgi/trace` else `PHONE_DEFAULT_REGION`, and a `generate_lead` data-layer push with the E.164 phone and email the server returned. Does nothing without the platform module's `window.pkdePhone`; a page without a phone field downloads none of the library. **After the bump the theme's `package.json` must list `intl-tel-input`** (`npm install --save-exact intl-tel-input@29.5.4`), or the Vite build fails with "Failed to resolve import". Documented in `docs/phone-numbers.md`; a test pins the no-CDN rule.

## 2026.10.26.9

- Fix (bb, tcs): a product search results page was titled "Shop" because the page-header composer checked `is_archive()` before `is_search()`; the search comes first now. A test pins the order.
- Optional shared search (tcs and bb carried the same code): Relevanssi with live results. `app/search.php` (sort choices handed to Relevanssi as meta fields, the plugin's stylesheet dequeued, six results, the kit's results template through `relevanssi_live_search_results_template` unless the theme has its own, product searches in the shop's template), the partials `product-search` and `live-search-results`, `resources/css/live-search.css`, and `bin/relevanssi-settings.php` (`wp eval-file`). Inert while the plugins are inactive. The free editions of both plugins are what tcs and bb use. Documented in `docs/woocommerce-templates.md` ("Search"). Verified in Chrome on a dev stack with WooCommerce, Relevanssi 4.28 and Live Ajax Search 2.6.
- Header options, both opt-in: a search icon that opens a panel under the bar (`pkde_kit/header_search` filter, `partials/header-search`, toggle in `kit.js`), and a configurable breakpoint for the mobile menu (`pkde_kit/nav_breakpoint` filter: sm default, md, lg, xl, 2xl; `App\nav_classes()`).
- Tests: the search filters and files, the sort-field map, the breakpoint sets, the header search partial.

## 2026.10.26.8

- Accessibility (bb): the unselected stars of the review-form star picker are neutral-500 (about 5:1 on white) instead of the pale neutral-200 track; they are controls, so WCAG 1.4.11 asks for 3:1. The selected stars stay amber-700, and the read-only rating keeps its pale track. A Pest test pins the colours and a node test checks neutral-500 against white and neutral-50 for the generated palette.

## 2026.10.26.7

- Fix (bb): `.prose-content > * + * { margin-top: 1em }` no longer opens a 16px white stripe between full-width Sections (`.prose-content > .wp-block-sage-section { margin-top: 0 }`).
- Product page (`resources/css/shop-pages.css`): the gallery zoom trigger is a round white button over the top-right of the main image with a drawn magnifier, its screen-reader label kept (the emoji link is gone); related products and upsells are two across on a phone, three from 640px and four from 1024px; the Reviews tab shows the reviews as quiet cards without the grey avatar placeholders beside a card holding the form, and the star picker is five drawn stars (WooCommerce's star font is not loaded, so it was blank), filled up to the one under the pointer or chosen. The product's reading width (46rem) applies to the Description and Additional information tabs, not to Reviews. Checked in Chrome with WooCommerce 11.2.1 at 1440 and 390.
- Not a kit change: the kit's carousel keeps its pause control (WCAG 2.2.2) as the default; a site that prefers dots without a pause button overrides it on its own.

## 2026.10.26.6

- Checkout polish (bb): shipping methods are bordered selectable rows on the checkout as well as the basket (the native radio stays, the whole row is its label), and "Ship to a different address?" is styled as a section heading with its checkbox, instead of a small form label.
- The social-links partial's list always carries the class `pkde-social-links` (next to its `aria-label`), so a site can scope a footer rule around it; its links are `display: grid` on purpose, and a rule like `.content-info li a { display: inline-block }` breaks them. Documented in `docs/woocommerce-templates.md`.

## 2026.10.26.5

- One gutter token (bb): every kit page container pads its sides with `px-[var(--page-gutter,1.5rem)]` instead of a fixed `px-6` (page header, post grid, single post, shop archive and body, the Hero and Hero Slide wrappers, the themed loop sections, `container_class()`'s default), so a site changes the page gutter in ONE place (`--page-gutter` in its theme.css, 1.5rem unset). The Section opt-in measure is derived from it: `--pkde-section-measure: calc(var(--container-content) - 2 * var(--page-gutter))`, and the `gutter` spacing preset in theme.json is `var(--page-gutter)`. Same 1.5rem by default, so nothing moves. A test fails on a fixed `px-6` next to `max-w-content`.
- The product's long description (`.single-product .woocommerce-Tabs-panel`) keeps a 46rem reading width instead of the full page measure.

## 2026.10.26.4

- Shop polish (bb, tcs; `resources/css/shop-pages.css`, imported by `kit.css`): rating stars drawn with a mask in place of the "Rated 5.00 out of 5" text (the label stays for screen readers, amber-700 for contrast); equal-height product cards with the button at the bottom and "View cart" as a text link instead of a second pill; a product category's description in a closed `<details>` accordion ("About {category}", first page, `app/woocommerce/display.php`); basket, checkout and account styling for the classic shortcode pages (5.5rem thumbnails, items beside a sticky totals card, cross-sells under the items, the order review beside the billing details, cards on phones), on the page measure instead of the 48rem reading column. All on kit tokens, scoped by body class, `:where()` for easy overrides.
- Social icons: `App\social_links()` (options `client_facebook`, `client_instagram`, `client_linkedin`, `client_x`, `client_youtube`) and the `pkde-kit::partials.social-links` partial print 44px round icon links with an `aria-label` each; nothing when none is set. Documented in `docs/woocommerce-templates.md`.
- Tests: social link listing (valid URLs, fixed order), the shop CSS import and its key rules, the partial's labels.

## 2026.10.26.3

- Layout (bb): the page containers of the single post, the shop archive and its body, the page header and the post grid use `max-w-content` (the `--container-content` token the header and footer use) instead of `max-w-6xl`/`max-w-7xl`, so the shop and product pages are as wide as the header (1152px against 1232px of content before). One token moves all of them. A test fails on a fixed `max-w-6xl`/`max-w-7xl` in a kit view.
- The Section block's content measure is opt-in with ONE variable: `--pkde-section-measure` (for example `calc(var(--container-content) - 3rem)` in the theme's `theme.css`) sizes the Section's direct children and its wide ones instead of theme.json's `contentSize`, which is a 48rem reading width on most sites and left Section content narrower than the header. Unset, nothing changes. The rule is unlayered on purpose (WordPress's own layout rule is). Set the `gutter` spacing preset to 1.5rem in theme.json as well so the Section's padding equals the header's `px-6`. A nested Group keeps its own measure, which is how reading-width text sits in a full-width Section.

## 2026.10.26.2

- `theme:check` fails on the 600 step of `brand`/`accent` only when NEITHER white nor near-black text reaches 4.5:1 (a narrow mid-tone band). When only white fails, both commands print a note ("use `text-brand-600-fg` for your own text on it") and the check passes, since the kit's own components use the foreground tokens. `acceptLowContrast` stays as an escape hatch. Sites that listed it only to get `theme:check` green can remove it. Tests cover the note, the failure and the escape hatch.

## 2026.10.26.1

- Foreground tokens (js): the generator emits `--color-{name}-{step}-fg` for every step of every colour (white when it reaches 4.5:1, otherwise the better of white and near-black), plus `--color-{name}-fg`, `--color-action-fg`, `--color-action-hover-fg`, `--color-highlight-fg`, as Tailwind utilities `text-brand-600-fg` and so on. The kit's components that put text on a brand colour use them instead of a hard-coded `text-white`: the post and product badges, the hover colour of the product-card button, the menu call-to-action, the Contact Form 7 submit and WooCommerce buttons on hover. A site with a light brand colour no longer has to override them; `acceptLowContrast` only covers a site's own white-on-brand uses. After the bump run `npm run theme:generate` and commit the regenerated `brand-tokens.css`.
- Tests: node:test cases (`npm test`, run in CI) run the real generator on a throwaway theme with a light, an amber and a dark colour and check every foreground token; a Pest test fails on a `bg-brand-N` or `bg-accent-N` with a hard-coded `text-white` in a view.

## 2026.10.26

- Accessibility (bb audit): the product-card rating stars use `text-amber-700` (5.0:1 on white; amber-500 gave 2.1:1 and axe flagged every card), and the Featured-post badge is `bg-amber-700` under its white icon. A test fails if a view uses an amber step lighter than 700 for text or a white-on-amber marker.
- `generate-theme-tokens.mjs` checks white text on the `brand-600` and `accent-600` steps: below 4.5:1, `theme:generate` warns and `theme:check` fails, naming the ratio and a darker hex of the same hue. A deliberate exception goes in `"acceptLowContrast": ["brand"]` in `config/brand-palette.json`. Documented in `docs/branding-tokens.md`.

## 2026.10.23

- `theme:check` says what is wrong: when a generated file (or the `theme.json` palette) differs from `config/brand-palette.json` it lists the files on stderr and tells you to run `npm run theme:generate`, instead of exiting 1 silently.
- The Sale and New badge links on product cards carry `rel="nofollow"`: every card links to `?sage_view=sale|new`, so crawlers spent their budget on those (already `noindex, follow`) views. A `robots.txt` Disallow was not used because it would stop crawlers reading the `noindex`. A test covers both links.
- `composer.json` pins `config.platform.php` to 8.3.0, so a `composer update` of the kit's own (dev) dependencies cannot pick a PHP 8.4-only release such as symfony/http-foundation 8.1.x. It has no effect on a site, which uses its own platform setting.

## 2026.10.22

- `generate-theme-tokens.mjs --check` (`npm run theme:check`) compares the parsed `settings.color.palette` of `theme.json` with the generated one instead of comparing bytes, so re-saving `theme.json` in another layout (Python's `json.dump`, an editor's formatter) no longer fails it; a real palette difference still does. Generating still expects the layout it writes, so keep `theme.json` in that layout when the generator is run.

## 2026.10.21.1

- Filterable containers: `\App\container_class($default, $context)` runs the `pkde_kit/container_class` filter, and Hero, Hero Slide, the themed loop-section wrapper and the page header, post grid, single post and shop containers use it, so a site with its own container class changes it in one filter instead of overriding markup (the default classes and the HTML are unchanged). New `pkde_kit/section_class` filter for the themed loop-section classes.
- Wording: the price-range separator in the active filter chips is translatable (`_x`), so every user-facing string of the kit goes through gettext with the one text domain `sage` (a site can change wording and punctuation with a `gettext` filter or a translation file). Tests check the domain and that no container is hard-coded.

## 2026.10.21

- First public release: the shared theme code of a Sage/Acorn site (helpers, native blocks, WooCommerce integration, editor fixes, shared JS and CSS, the brand-token generator and the `pkde-kit` views) as one Acorn package, unchanged in behaviour from the code it was extracted from. Shop request parameters ignore array values instead of raising a warning. GPL-2.0-or-later, SECURITY.md, a secret scan and a public-safety test in CI; the installed package contains no tests, docs or composer.json.
