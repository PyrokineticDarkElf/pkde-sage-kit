# Card text: lines, not words (TPL-280)

Every kit card limits its title and its text by **lines**, with CSS, so cards in a row keep their shape and a long text ends in an ellipsis. No card has a word count of its own and none ends in "… Continued".

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
| `--pkde-clamp-related-title`, `-related-excerpt` | the two above | related and recent posts |
| `--pkde-clamp-author-bio` | `--pkde-clamp-excerpt` | the author box |
| `--pkde-clamp-feature-title`, `-feature-text` | the two above | feature items (and so the pattern bank's feature cards) |
| `--pkde-clamp-quote` | 6 | a testimonial: generous, only a very long one is cut |
| `--pkde-clamp-tile-title`, `-category-title`, `-brand-title`, `-recent-title` | `--pkde-clamp-title` | category tiles, category cards, the brand card's name, recently viewed |

A value is a whole number of lines, or `none` for no clamp. Set them in `config/brand-palette.json` and `npm run theme:generate` writes them to `:root`:

```json
"text": { "clamp": { "title": 2, "excerpt": 4, "product-excerpt": 2, "quote": "none" } }
```

or in the site's CSS (`:root { --pkde-clamp-excerpt: 4; }`). A palette without `text.clamp` writes nothing and the defaults apply.

## A site's own card markup
A site that published a kit view (`vendor/pkde-kit/...`) or draws its own card gets the clamp by adding the kit's class to its title or text: `pkde-card__title`, `pkde-card__excerpt`, `pkde-product-card__title`, `pkde-product-card__excerpt`, `pkde-related__title`, `pkde-related__excerpt`, `pkde-author__bio`, and by printing `App\card_excerpt()` or `App\card_text()` instead of its own `wp_trim_words()`.
