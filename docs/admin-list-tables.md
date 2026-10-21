# Admin list-table styling

`resources/css/admin.css` provides restrained visual cues on WordPress post
list tables (`edit.php`) only. It is loaded by `admin_enqueue_scripts` in
`app/setup.php`; the block editor, frontend, WooCommerce orders, and other
plugin screens are not affected.

## Status colours

| Post status | Treatment |
| --- | --- |
| Published | Pale green row |
| Pending | Pale amber row |
| Draft | Pale rose row |

Each status receives a slightly stronger shade on hover, retaining normal
WordPress table interaction feedback.

## Rank Math column

`rank_math_seo_details` is fixed to 200px on post-list tables so its
editorial/SEO indicators have a stable, legible column width. The selector is
harmless when Rank Math is inactive because no matching column exists.

## Maintenance

Keep admin-only styles in `resources/css/admin.css` and scope them to an
explicit WordPress admin surface. This file is enqueued directly rather than
through Vite so wp-admin remains independent of the frontend asset pipeline.
