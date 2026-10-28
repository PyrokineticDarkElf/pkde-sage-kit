# Changelog

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
