# Entry Grid block and PNG-to-WebP sub-sizes

## `sage/entry-grid`: a listing of every entry of a post type

Recent Posts shows "the latest N" with a "view all" link. **Entry Grid** is the listing itself: a card grid of every published entry of one post type, for pages such as Portfolio or Services.

* **Post type:** any registered, viewable post type (a select in the editor), blog posts by default. An unknown or non-public type falls back to posts.
* **Count:** 0 (the default, "Show every entry") means all of them; otherwise the first N.
* **Order:** each entry's Order field (Page Attributes; post types that support it), then title.
* **No "view all" link**, a heading and description are optional, and the same Section theme and Card style settings as the other loop blocks apply (`pkde-card-*`).
* An empty result prints "No <type> published yet."

It reuses `partials/blocks/post-grid` and `partials/content`, which only use generic template tags, so any post type works. Register a post type, add the block to the listing page, and pick the type. The block is part of the block demo page.

## PNG sub-sizes are saved as WebP

`app/helpers.php` makes WordPress save generated PNG sub-sizes (thumbnail, medium, large, ...) as WebP at quality 80; the uploaded original is never touched. In one measured case a 435 KB PNG card image fell to 43 KB. It applies to sub-sizes generated after the change; regenerate existing ones with `wp media regenerate --only-missing`.

* Needs a server image editor with WebP support (Imagick with WebP, or GD built with it); WordPress checks, and keeps PNG where it is not available. The template's `php` container has Imagick with WebP (the `wpcli` container does not, so generate through the web or the php container when testing).
* Opt out with `add_filter('pkde_png_to_webp', '__return_false');`.
* The pure format and quality functions have Pest tests (`tests/Feature/ThemeHelpersTest.php`).
