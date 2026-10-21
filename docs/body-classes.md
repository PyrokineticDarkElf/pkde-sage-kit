# Taxonomy term body classes

Every single post *and* single product (`app/filters.php`'s `body_class` filter — `is_single()`
covers any public, non-page post type, not just `post`) gets one class per assigned term, on every
taxonomy registered for that post type: `{taxonomy}-{term-slug}`. A post tagged "Guide" gets
`post_tag-guide`; a product in the "Brand Two" brand with a Blue colour attribute gets
`product_brand-brand-two pa_colour-blue` — so CSS or JS can target "posts tagged X" or "products in
category Y" with no extra markup, something WordPress core's own `body_class()` doesn't provide for
a post's own terms (only for the *current archive* being viewed).

Only genuinely **public** taxonomies are included (`get_object_taxonomies($post_type, 'objects')`,
filtered on `->public`) — confirmed live against a real product page that this correctly excludes
taxonomies a plugin registers for its own internal bookkeeping rather than public use (WooCommerce's
`product_shipping_class`, for example, never shows up). `post_format` is skipped too, since core
already adds its own `single-format-{format}` class for that one.
