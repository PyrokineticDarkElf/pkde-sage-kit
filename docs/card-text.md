# Card text: lines, not words

The kit limits the text it pulls in **automatically** by **lines**, with CSS, so cards in a row keep their shape and a long text ends in an ellipsis: a post excerpt, a product's short description, related and recent posts, product, category and brand names in a loop, a WooCommerce review in the testimonials, the author bio. No card has a word count of its own and none ends in "… Continued".

**Text an editor typed into a block is never clamped by default**: a Feature grid's titles and texts, a testimonial of the manual or posts source, text and image, hero, CTA, the pattern bank's copy. A site may opt in (below).

## The two limits
- **Server (one helper, a safety cap):** `App\card_text($text)` strips tags, decodes entities once and keeps at most **40 words** (the filter `pkde_kit/card_text_words`, `0` for no cap), so the HTML stays small and a reader without CSS is not flooded. `App\card_excerpt($post)` is the post's written excerpt, else the start of its content, through `card_text()`. A screen reader reads the whole capped text: the clamp only hides it visually.
- **CSS (the visible limit):** `resources/css/clamp.css`: `display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: N; line-clamp: N; overflow: hidden`.

## The tokens
| Token | Default | Reads |
|---|---|---|
| `--pkde-clamp-title` | 2 | every card title |
| `--pkde-clamp-excerpt` | 3 | every card's excerpt or description |
| `--pkde-clamp-post-title`, `-post-excerpt` | the two above | blog cards, the latest and featured posts blocks |
| `--pkde-clamp-product-title`, `-product-excerpt` | the two above | the product card (the description shows in the list view) |
| `--pkde-clamp-related-title` (2), `-related-excerpt` (**1**, the card's design: it does not follow `--pkde-clamp-excerpt`) | | related and recent posts |
| `--pkde-clamp-author-bio` | `--pkde-clamp-excerpt` | the author box |
| `--pkde-clamp-quote-review` (the older `--pkde-clamp-quote` still works) | 6 | a WooCommerce review in the testimonials (source "reviews"): **not cut, it scrolls**: the text box is at most this many lines high and scrolls inside the card; `none` for no limit |
| `--pkde-clamp-tile-title`, `-category-title`, `-brand-title`, `-recent-title` | `--pkde-clamp-title` | category tiles, category cards, the brand card's name, recently viewed |

A value is a whole number of lines, or `none` for no clamp.

**Opt-in, typed text** (no default, nothing is clamped unless you name it in `text.clamp`; the generator then writes the rule): `feature-title`, `feature-text` (Feature grid items) and `quote-typed` (manual and posts testimonials), for example `"text": { "clamp": { "feature-text": 4 } }`. Set them in `config/brand-palette.json` and `npm run theme:generate` writes them to `:root`:

```json
"text": { "clamp": { "title": 2, "excerpt": 4, "product-excerpt": 2, "quote-review": 8 } }
```

or in the site's CSS (`:root { --pkde-clamp-excerpt: 4; }`). A palette without `text.clamp` writes nothing and the defaults apply.

## A site's own card markup
A site that published a kit view (`vendor/pkde-kit/...`) or draws its own card gets the clamp by adding the kit's class to its title or text: `pkde-card__title`, `pkde-card__excerpt`, `pkde-product-card__title`, `pkde-product-card__excerpt`, `pkde-related__title`, `pkde-related__excerpt`, `pkde-author__bio`, and by printing `App\card_excerpt()` or `App\card_text()` instead of its own `wp_trim_words()`.

## A review scrolls, it is never cut
A WooCommerce review in the Quotes block (`pkde-quotes--reviews`) shows in full: its text box is at most `--pkde-clamp-quote-review` lines high (6) with `overflow-y: auto`, so a longer review scrolls inside its card and the cards keep their height. A review that fits does not scroll at all. A box that scrolls is keyboard focusable (`tabindex="0"`, a visible focus ring) and is a region named "Review by <name>" (`resources/js/quote-scroll.js`), has a thin scrollbar in the section's muted colour, `overscroll-behavior: contain`, and a soft fade at the bottom edge while there is more to read.
