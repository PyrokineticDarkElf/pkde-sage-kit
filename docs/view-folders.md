# Views grouped by use: blog/, shop/, pages/

The template keeps the default views in `resources/views/` in three folders. The kit makes WordPress find them (`app/template-folders.php`).

| Folder | Views |
|---|---|
| `blog/` | `home` (posts page), `archive`, `category`, `tag`, `author`, `single` (a post) |
| `shop/` | `archive-product` (the shop and the fallback for any product archive), `taxonomy-product_cat`, `taxonomy-product_tag`, `taxonomy-product_brand`, `single-product` |
| `pages/` | `page`, `front-page`, `404`, `search` |
| (flat) | `index`, the page templates chosen in the editor (`template-about`, `template-contact`, ...), `layouts/`, `sections/`, `forms/` |

**How a view is found.** WordPress asks for a list of names, most specific first (`category-news.php`, `category.php`, `archive.php`, `index.php`). The kit adds the folder file right after each flat one: `category-news.php`, `blog/category-news.php`, `category.php`, `blog/category.php`, ... Acorn turns the list into view files. So:

* **A flat view always wins.** A site that has `resources/views/category.blade.php` keeps using it; the folder default is only the fallback. Nothing has to move when a site takes the kit.
* **Override one archive with one file.** `resources/views/taxonomy-product_brand.blade.php` (or `taxonomy-product_brand-{slug}.blade.php` for one brand) changes the brand archive and nothing else. The same for `category`, `tag`, `author`, `home`, `archive`.
* **A site may adopt the folders too:** `git mv resources/views/page.blade.php resources/views/pages/`. View names change with the path (`pages.page`), so a composer registered for `page` needs the new name too.
* **Page templates stay where they are.** The editor stores a page template's file name, so moving `template-about.blade.php` would unassign every page that uses it.
* **Custom post types** (`single-project`, `archive-project`) and other taxonomies stay flat.

**Defaults, kept thin.** Each is the page header plus a kit partial: `blog/category` and `blog/tag` add `pkde-kit::partials.term-description` (the term's description under the title) before `post-grid`; the three shop taxonomy views include `pkde-kit::partials.shop-archive` (WooCommerce prints a product category's description inside the product list); `shop/archive-product` serves the shop page and any product archive without its own view.
