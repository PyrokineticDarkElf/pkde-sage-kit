# Legal documents (`single-pkde_legal`)

The platform's `legal` module (pkde-platform) provides the `pkde_legal` post type, one document per policy at `/<slug>/`. The kit prints it with `resources/views/single-pkde_legal.blade.php`:

- the title (H1) and the breadcrumbs;
- a line under it with only the parts that are set: **Effective from** {date} · **Last updated** {the reviewed date, else the date the document changed} · **Version** {v}, from the document's fields;
- the text in a readable column with the blog single's type (the `--pkde-post-*` variables, so a site's typography applies), every heading given an anchor id on the server, and a contents list from the h2 and h3 headings **when there are three or more** (scroll-spied, smooth scrolling that respects reduced motion; a collapsed list above the text on a narrow screen);
- print styles (`resources/css/legal.css`): no header, footer, breadcrumbs or contents list, black text, each link's address printed after it;
- **no public version history**: the revisions stay in wp-admin.

A site that needs another layout adds its own `single-pkde_legal.blade.php` (a flat view wins over the kit's). Migrating a site's legal Pages into documents: pkde-deploy `bin/deploy/legal-migrate.php`.
