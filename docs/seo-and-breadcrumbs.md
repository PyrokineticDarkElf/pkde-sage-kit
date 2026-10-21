# SEO & breadcrumbs

RankMath (see [Default plugins](../../../../../bin/docs/default-plugins.md)) owns all of the
general SEO surface — meta description, Open Graph tags, canonical URLs, XML sitemaps, schema.org
markup — via its own `wp_head()` hooks. The theme deliberately adds none of this itself;
duplicating it would just create conflicting tags.

**Breadcrumbs** are switched on by `bin/setup` (`rank-math-options-general.breadcrumbs = on`)
and rendered via `resources/views/partials/breadcrumbs.blade.php`, included in
`partials/page-header.blade.php` (covers About/Contact/Blog/Archive/Search/404 — every template
that already shares that partial) and at the top of `content-single.blade.php`'s header (single
posts build their own header, not `page-header`). **Not** included in `front-page.blade.php`, so
Home never shows a breadcrumb trail — by construction, not a conditional check.

One detail if you're touching that partial: RankMath's own output already includes a
`<nav aria-label="breadcrumbs">` wrapper — the partial styles it directly rather than nesting a
second `<nav>` landmark around it, which would be invalid/redundant markup.

**Semantic structure**, generally: single `<h1>` per page (page/post title, or the front page's
hero headline — never both), a correct heading hierarchy within each section (feature grid
items are `<h3>` nested under a section `<h2>`, blog cards are `<h2>` inside the page's own `<h1>`
context, etc.), proper landmark elements (`<header>`, `<nav>`, `<main>`, `<footer>`, `<article>`),
a skip-to-content link, and `lang`/`viewport`/`charset` all in place via `layouts/app.blade.php`.
