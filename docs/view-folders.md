# Views grouped by use: blog/, shop/, pages/

The template keeps the default views in `resources/views/` in three folders. The kit makes WordPress find them (`app/template-folders.php`).

| Folder | Views |
|---|---|
| `blog/` | `home` (posts page), `archive`, `category`, `tag`, `author`, `single` (a post) |
| `shop/` | `archive-product` (the shop and the fallback for any product archive), `taxonomy-product_cat`, `taxonomy-product_tag`, `taxonomy-product_brand` (the template's folder copies; the kit now supplies `single-product` itself) |
| `pages/` | `front-page` (the demo front page; `page`, `404` and `search` are the kit's now) |
| (flat) | `index`, the page templates chosen in the editor (`template-about`, `template-contact`, ...), `layouts/`, `sections/`, `forms/` |

**How a view is found.** WordPress asks for a list of names, most specific first (`category-news.php`, `category.php`, `archive.php`, `index.php`). The kit adds the folder file right after each flat one: `category-news.php`, `blog/category-news.php`, `category.php`, `blog/category.php`, ... Acorn turns the list into view files. So:

* **A flat view always wins.** A site that has `resources/views/category.blade.php` keeps using it; the folder default is only the fallback. Nothing has to move when a site takes the kit.
* **Override one archive with one file.** `resources/views/taxonomy-product_brand.blade.php` (or `taxonomy-product_brand-{slug}.blade.php` for one brand) changes the brand archive and nothing else. The same for `category`, `tag`, `author`, `home`, `archive`.
* **A site may adopt the folders too:** `git mv resources/views/page.blade.php resources/views/pages/`. View names change with the path (`pages.page`), so a composer registered for `page` needs the new name too.
* **Page templates stay where they are.** The editor stores a page template's file name, so moving `template-about.blade.php` would unassign every page that uses it.
* **Custom post types** (`single-project`, `archive-project`) and other taxonomies stay flat.

**Defaults, kept thin.** Each is the page header plus a kit partial: `blog/category` and `blog/tag` add `pkde-kit::partials.term-description` (the term's description under the title) before `post-grid`; the three shop taxonomy views include `pkde-kit::partials.shop-archive` (WooCommerce prints a product category's description inside the product list); `shop/archive-product` serves the shop page and any product archive without its own view (three lines: `@include('pkde-kit::partials.shop-page')`; the layout, its variables and hooks are in [woocommerce-templates.md](woocommerce-templates.md#the-standard-shop-archive)).

## The kit supplies the archive views (2026.11.05)

The kit's `resources/views/` root holds `home`, `archive`, `category`, `tag`, `author`, `search`, `index`, `single`, `page`, `404`, `single-product`, `archive-product` and the three `taxonomy-product_*` views (the kit's views directory is the last location the view finder searches). A theme that has no file of that name uses the kit's, so a site no longer carries a copy: it **deletes** its own `home`, `archive`, `category`, `tag`, `author`, `search`, `index`, `single`, `page`, `404`, `single-product`, `archive-product` and `taxonomy-product_*` views when they only repeat these, and adds a flat file again for the ONE archive it wants to change. The blog archives are the page header, the term's description (category and tag), the posts and numeric pagination (`partials/pagination`, the shop's look); the shop archives are `partials/shop-page`.

Because the kit's `category` is found before `index`, a site that relied on its own `index.blade.php` for archives now gets the kit's page header and post grid; add a flat `archive.blade.php` (and the others) to keep a different design. `single` is the blog single (docs/blog-single.md); `page` prints the shared page header on every page except the front page, and `pkde_kit/page_header` (`bool $show`, `WP_Post $page`) lets a site skip it for a page whose content brings its own title. The template no longer keeps its own copies of these views; `pages/front-page` (the demo front page) stays in the template.
