# Single posts: sidebar, table of contents, related posts

Every single post (`resources/views/partials/content-single.blade.php`) is a two-column layout on
desktop (`lg:grid lg:grid-cols-12` — content in `lg:col-span-8`, sidebar in `lg:col-span-4`;
stacks to one column below that) with:

- **A table of contents.** `app/TableOfContents.php` parses the post's already-block-rendered
  content with `DOMDocument`. A unique, valid existing heading `id` is retained; missing,
  invalid, or duplicate IDs receive a unique slugified replacement (`-2`, `-3`, ...), avoiding
  collisions with any other anchor in the content. It returns both the anchor-tagged HTML (used
  in place of a plain `the_content()` call) and the heading list `partials/toc.blade.php` renders
  as a `sticky` linked list. Posts with fewer than two headings get no ToC block at all.
- **Copyable heading links.** Every ToC heading has a keyboard-accessible copy-link button. The
  link icon appears on heading hover or focus and copies that section's canonical URL fragment;
  its accessible label confirms success. Browsers which deny Clipboard API access still retain the
  ordinary linked ToC and heading anchors.
- **Scroll-spy.** `resources/js/app.js` (previously empty — this is the theme's first real
  frontend script) uses an `IntersectionObserver` to add `data-active="true"` to whichever ToC
  link matches the heading currently in view — styled via a plain `data-[active=true]:` Tailwind
  variant on the link, no separate CSS class needed. Fully guarded (`if
  (document.querySelector('[data-toc]'))`), so it's a no-op on every page without a ToC.
- **An author box** (`partials/author-box.blade.php`) — avatar, name/link, a 20-word bio excerpt.
- **Related posts** (`partials/related-posts.blade.php`) — up to 3 posts sharing the current
  post's category, via a secondary `WP_Query` (`wp_reset_postdata()` afterwards, as always when
  using one inside the main loop). Rendered with a **new** card style,
  `partials/related-post-card.blade.php` — small square image left, title + one-line excerpt
  right — distinct from the vertical, image-top card (`partials/content.blade.php`) used on the
  blog grid.
- **Sitewide categories and tags** (`partials/sidebar-terms.blade.php` — one partial, reused for
  both taxonomies via a `$taxonomy` prop).

**Prev/next navigation** sits below the content (not in the sidebar):
`partials/post-navigation.blade.php`, `get_previous_post_link()`/`get_next_post_link()` (adjacent
by date — a different mechanism from the existing `$pagination()` composer method, which handles
`<!--nextpage-->` multi-page posts).
