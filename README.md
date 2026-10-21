# pkde/sage-kit

The shared theme code of every PKDE WordPress site, as an Acorn package. Public, GPL-2.0-or-later. Design: ADR 0007 in the starter template
(`docs/decisions/template/0007-shared-code-as-versioned-packages.md`); working guide: the template's `docs/process/shared-packages.md`. Vulnerabilities: see `SECURITY.md`.

## What is in it

| Path | What |
|---|---|
| `app/` | the theme's PHP, **unchanged and still in the `App\` namespace** (so views and sites that call `App\...` keep working): `setup.php`, `filters.php`, `blocks.php`, `woocommerce.php` and `helpers.php` are loaded by Composer's autoloader (`autoload.files`, each does nothing outside WordPress); classes such as `ReadingTime`, `ShopFilters`, `TableOfContents`; the view composers in `app/View/Composers`; `app/woocommerce/` (only active with WooCommerce) |
| `src/KitServiceProvider.php` | registers the `pkde-kit` view namespace, the anonymous components `<x-pkde-kit::...>`, the legacy view fallback and the composers; discovered by Acorn (`extra.acorn.providers`) |
| `resources/blocks/` | every native block (`block.json`, `render.php`, `edit.jsx`), registered by `app/blocks.php`; a block of the same name in the theme replaces the kit's |
| `resources/views/` | `partials/`, `components/`, `woocommerce/` as the `pkde-kit` namespace |
| `resources/js/` | `kit.js` (front end), `kit-editor.js` (block editor: canvas guard and parity classes, Gap label, Animation panel, every block's edit component), carousel, lightbox, shared editor helpers |
| `resources/css/` | `kit.css` (the shared front-end styles, imported by the theme's `app.css`), `admin.css` |
| `bin/generate-theme-tokens.mjs` | the brand-token generator (writes into the **theme**: the current directory, or `PKDE_THEME_DIR`) |
| `docs/` | the theme documentation (not installed on sites) |

## Use

In the theme's `composer.json`: `"repositories": [{"type": "vcs", "url": "https://github.com/PyrokineticDarkElf/pkde-sage-kit"}]` and
`"require": {"pkde/sage-kit": "<exact tag>"}`. Then, in the theme:

* `resources/js/app.js`: `import '../../vendor/pkde/sage-kit/resources/js/kit.js';` and `editor.js`: `import '../../vendor/pkde/sage-kit/resources/js/kit-editor.js';`
* `resources/css/app.css`: `@import "../../vendor/pkde/sage-kit/resources/css/kit.css";` **and** `@source "../../vendor/pkde/sage-kit/app";` / `@source "../../vendor/pkde/sage-kit/resources";` (Tailwind skips gitignored `vendor/`; without the `@source` lines the kit's utility classes get no CSS). Import the site's own `site.css` after the kit.
* `vite.config.js`: the alias `'@generated': '/resources/js/generated'` (the kit's block editors import the generated colour options from the theme).
* `package.json`: `"theme:generate": "node vendor/pkde/sage-kit/bin/generate-theme-tokens.mjs"` (and `--check` for `theme:check`), and the npm packages the kit imports (`embla-carousel` and its autoplay plugin, `@wordpress/*` come from WordPress's externals).
* `functions.php` keeps Acorn's boot; the theme's own `app/*.php` files are loaded only when they exist.

Override one view: copy it to `resources/views/vendor/pkde-kit/<same path>` in the theme. See the template's guide.

## Develop and release

* `composer install`, `composer lint`, `composer test`. The tests need no WordPress: pure functions in `helpers.php` and a check that every `pkde-kit::` view a file names exists.
* The real proof of a change is the layout fingerprint of a site's pages before and after (`pkde-deploy`'s `fingerprint.sh`).
* The repository is public: Composer needs no token (`"repositories": [{"type": "vcs", "url": "https://github.com/PyrokineticDarkElf/pkde-sage-kit"}]`).
* Release: add a `## YYYY.MM.DD` section to `CHANGELOG.md`, push, wait for CI, then `bin/release.sh YYYY.MM.DD`. That script is the only way a tag is made (no branch protection on the free plan). Roll a site back by pinning the previous tag.

## License

GPL-2.0-or-later (see `LICENSE`), the same as WordPress.
