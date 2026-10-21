# "New" badge on recent posts & products

Blog cards (`partials/content.blade.php`) and product cards (`woocommerce/content-product.blade.php`)
show a small brand-coloured "New" pill (top-right, opposite the category pill/sale badge already on
the top-left) on whichever of that post type's items are currently the most recent — 3 posts, 4
products, per `App\RecentContent::RECENT_COUNTS` in `app/RecentContent.php`. Edit that array to
change the counts or add another post type; there's no admin settings screen for this on purpose —
it's a small, fixed display rule, not something that needs one.

**Computed at render time, not stored.** `RecentContent::isNew($postId, $postType)` just checks
whether a post is in that post type's N-most-recently-published ID list (one `get_posts()` call,
memoised per post type so a whole grid of cards only queries once, not once per card) — no "New"
taxonomy term ever gets created or removed, and nothing depends on WP-Cron. That's a deliberate
choice over the more obvious-looking alternative (tag new posts on publish, run a scheduled job to
untag the oldest ones once you're over the limit): WP-Cron only ever runs when a request hits the
site, so a scheduled cleanup can silently stop firing for a long stretch on a quiet site — exactly
the kind of site this template targets, and exactly when a "new" cue matters most. A stateless
read at render time has nothing to fall behind.
