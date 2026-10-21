# Changelog

## 2026.10.21.1

- Filterable containers: `\App\container_class($default, $context)` runs the `pkde_kit/container_class` filter, and Hero, Hero Slide, the themed loop-section wrapper and the page header, post grid, single post and shop containers use it, so a site with its own container class changes it in one filter instead of overriding markup (the default classes and the HTML are unchanged). New `pkde_kit/section_class` filter for the themed loop-section classes.
- Wording: the price-range separator in the active filter chips is translatable (`_x`), so every user-facing string of the kit goes through gettext with the one text domain `sage` (a site can change wording and punctuation with a `gettext` filter or a translation file). Tests check the domain and that no container is hard-coded.

## 2026.10.21

- First public release: the shared theme code of a Sage/Acorn site (helpers, native blocks, WooCommerce integration, editor fixes, shared JS and CSS, the brand-token generator and the `pkde-kit` views) as one Acorn package, unchanged in behaviour from the code it was extracted from. Shop request parameters ignore array values instead of raising a warning. GPL-2.0-or-later, SECURITY.md, a secret scan and a public-safety test in CI; the installed package contains no tests, docs or composer.json.
