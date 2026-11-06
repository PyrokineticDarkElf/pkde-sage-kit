# The blog single (kit default, MGR-66)

One layout for every site, tcs's: a header (breadcrumbs, category pills, the title, the meta), a prominent featured image, the reading column with the
contents list beside it, then the share row, previous and next post, the author box, related posts, recent posts and the tags, and the Article schema.
`resources/views/single.blade.php` and `partials/content-single.blade.php` are the kit's; **a site does not copy them**. It changes the look with
variables, switches a section off with one filter and adds to the layout with actions.

## What it shows

| Section (`pkde_kit/single_show`) | Content |
|---|---|
| `breadcrumbs` | Rank Math's trail (the page header's partial) |
| `categories` | the post's categories as pills above the title |
| `meta` | date, the author (linked to the author archive while it exists), reading time |
| `hero` | the featured image, wide, with rounded corners; nothing when the post has none |
| `toc` | the table of contents rail (sticky from 1024px, a collapsed list above the text on a phone), the active heading marked |
| `share` | plain share links (Facebook, X, LinkedIn, WhatsApp, email) and a Copy link button; no script from a network |
| `tags` | the post's tags as pills |
| `author_box` | avatar, name (linked), the first 20 words of the bio |
| `navigation` | previous and next post |
| `related` | posts in the same categories (3, `pkde_kit/related_posts_count`) |
| `recent` | the newest posts, not the post itself or a related one (3, `pkde_kit/recent_posts_count`) |
| `schema` | schema.org Article as JSON-LD; **not printed while Rank Math is active** (it prints its own; never both) |

## Hooks

Filter `pkde_kit/single_show` (`bool $show`, `string $section`, `?WP_Post $post`): return `false` to hide a section.
Filter `pkde_kit/author_archives` (`bool`): whether a byline links to the author archive (default: unless Rank Math has author archives off).
Filter `pkde_kit/share_links` (`array label => url`, `string $url`, `string $title`), `pkde_kit/single_schema` (`array`, `?WP_Post`; an empty array prints nothing).
Actions, each given the `WP_Post`, printed inside the layout:

| Action | Where |
|---|---|
| `pkde_kit/single_before_hero` | above the header |
| `pkde_kit/single_before_meta`, `pkde_kit/single_after_meta` | around the meta line |
| `pkde_kit/single_after_hero` | under the featured image |
| `pkde_kit/single_before_content`, `pkde_kit/single_after_content` | around the text |
| `pkde_kit/single_before_sidebar`, `pkde_kit/single_after_sidebar` | around the contents rail |
| `pkde_kit/single_before_related`, `pkde_kit/single_after_related` | before the author box and related list, and after the tags |

```php
add_action('pkde_kit/single_after_content', fn ($post) => print('<aside class="newsletter">...</aside>'));
add_filter('pkde_kit/single_show', fn ($show, $section) => $section === 'recent' ? false : $show, 10, 2);
```

## Look: variables on `:root`

Each has a kit-token fallback, so a site that sets none still looks right (`resources/css/post-single.css`).

| Variable | What | Default |
|---|---|---|
| `--pkde-post-line` | rules, borders, pills | `--color-neutral-200` |
| `--pkde-post-muted` | body text, breadcrumbs, meta | `--color-neutral-600` |
| `--pkde-post-ink` | title, headings, the active contents link | `--color-neutral-950` |
| `--pkde-post-accent` | links in the text, the contents heading and active mark | `--color-brand-700` |
| `--pkde-post-surface` | pill and panel background | `--color-white` |
| `--pkde-post-title-weight`, `--pkde-post-title-size` | the H1 | the page header's weight, `clamp(2.2rem, 4.4vw, 3.8rem)` |
| `--pkde-post-heading-weight` | h2 to h4 in the text | 300 |
| `--pkde-post-text-size` | the reading text | 1.0625rem |
| `--pkde-post-measure` | text column width | 44rem |
| `--pkde-post-rail-width`, `--pkde-post-sticky-top` | the contents rail, where it sticks | 15rem, 6.5rem |
| `--pkde-post-hero-radius` | the featured image's corners | 32px |

Also `--pkde-post-title-font`, `--pkde-post-heading-font`, `--pkde-post-text-leading`, `--pkde-post-hero-max-height` (34rem), `--pkde-post-hero-bg` (the colour behind the image while it loads) and `--pkde-post-image-radius` (20px).

## A post type of its own

`partials/content-single-{type}` (in the theme, or `vendor/pkde-kit/`) is used for that type before the kit's.
