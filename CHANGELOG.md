# Changelog

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
