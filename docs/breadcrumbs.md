# Breadcrumbs (the kit owns them, MGR-77 parity)

The trail is the same on every site, whatever its Rank Math settings: `Home / Blog / Category / Post title`, with " / " between the crumbs.

* **Rank Math installed:** its trail is used, and its BreadcrumbList schema stays. The kit forces Rank Math's breadcrumb settings and labels through
  `rank_math/frontend/breadcrumb/settings` and `/strings` (separator `/`, label `Home`, the current page as the last crumb, ancestors and the blog page
  shown, no prefix, its English formats for the 404, search and archive crumbs), and declares theme support for `rank-math-breadcrumbs`, which is how Rank
  Math decides the module is on, so the wp-admin switch no longer matters.
* **Rank Math absent (or `pkde_kit/force_rank_math_breadcrumbs` false and the module off):** the kit builds the trail itself, in the same markup
  (`<nav aria-label="breadcrumbs" class="rank-math-breadcrumb"><p><a>Home</a><span class="separator"> / </span> ... <span class="last">Title</span></p></nav>`),
  for posts, pages, categories, tags, taxonomies, the posts page, authors, dates, search, 404 and paged lists; on shop pages from WooCommerce's own crumbs.
  It prints no schema.
* The partial `partials/breadcrumbs` (`\App\breadcrumb_trail_html()`) is the only place a trail is printed; the page header and the blog single use it.

Hooks: `pkde_kit/breadcrumb_standard` (array `settings`, `strings`), `pkde_kit/force_rank_math_breadcrumbs` (bool), `pkde_kit/breadcrumb_html` (string).
Style the markup directly (`.breadcrumbs`, `.rank-math-breadcrumb`, `.separator`, `.last`); the page header colours it with `--pkde-page-header-crumb`.
The drift check (pkde-deploy 2026.11.06.1) has a row for the standard and one for Rank Math's module.
