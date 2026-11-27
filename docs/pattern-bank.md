# The pattern bank

36 block patterns in 12 families, 2 to 4 variants each, named **"Family: Variant"** (for example "Featured products: One large card and a list") with one pattern category per family in the inserter (Hero, Featured products, Product categories, Latest posts, Feature grid, Text and image, Testimonials, Call to action, FAQ, Contact, Stats, Logos). A page built from them has rhythm: each variant ships with a Brand theme chosen so that solid and light alternate inside a family. A site changes the theme of any section with one click (Section > Style).

| Family | Variants |
|---|---|
| Hero | Split, picture right · Full-width picture · Centred text · Split with figures |
| Featured products | Four across · Row that scrolls · One large card and a list · Heading beside three products |
| Product categories | Tiles · Cards · Chips |
| Latest posts | Three cards · One large post and a list · Compact list |
| Feature grid | Icons · Numbered steps · Picture cards |
| Text and image | Picture left · Picture right with a figure · Mosaic |
| Testimonials | One large quote · Three cards · Carousel |
| Call to action | Centred band · Split with a picture · Boxed card · Inline strip |
| FAQ | One column · Intro beside the questions |
| Contact | Details beside the form · Picture and details · Compact strip |
| Stats | A row of figures · Figures beside a picture |
| Logos | A single row · A grid |

(The catalogue the manager's brief listed adds up to 36 patterns, not 37.)

Each pattern has a preview picture in `docs/pattern-previews/<slug>.jpg` (the soft preset, 1440 px, the pattern's own Brand theme). The template's `/block-demo/` page shows every pattern by family: it is the catalogue.

## How they are built

- Only from the kit's blocks and a short list of core blocks (paragraph, heading, columns, buttons, list). The outer block of every pattern is a **Section locked to content** (`templateLock: contentOnly`) with a Brand theme: an Editor changes the words, pictures and links and cannot move, add or delete blocks; an Administrator can unlock it.
- The same elements in every variant of a family, so a site can swap one variant for another without losing content.
- Placeholder content only: neutral wording, no lorem ipsum, no claim about a business, no link off the site (`/contact/`, `/about/`, `/shop/` and `/blog/` are the standard pages). A picture slot is a **placeholder frame** in the section's colours (`placeholder` on the block) until the editor chooses a picture; a logo slot is a placeholder tile.
- Colours come from the section and card themes and the corners from the [shape tokens](branding-tokens.md) and concentric card radii (`--pkde-card-radius`), so every pattern follows the site's palette, `shape` preset and `buttons` declaration.
- Contact uses the Site details blocks (contact details, hours, social links) and a **Contact form** block (`sage/contact-form`: the Contact Form 7 form tagged with the key `contact`, shown to an editor with a note where there is none).

## Block options the variants needed (additive, nothing removed)

`sage/hero`: `placeholder`, a second button and a heading level in the full-width layout. `sage/text-and-image`: `placeholder` (0, 1 or 3 frames), `badgeValue` and `badgeLabel` (a figure laid over the picture). `sage/cta`: styles `split` and `boxed`, `placeholder`. `sage/feature-grid`: variants `steps` and `stats`; `sage/feature-item`: `placeholder`. `sage/product-loop`: `layout` (grid, rail, feature, intro). `sage/product-categories`: layout `chips`. `sage/latest-posts`: `layout` (cards, feature, list). `sage/accordion`: `singleColumn`. `sage/gallery`: `placeholders` and `fit` (contain), the logos. New block `sage/contact-form`. Every new attribute has a default that is what the block did before, so existing pages are unchanged.

## Adding a pattern

A file in `resources/patterns/` with the header (Title "Family: Variant", Slug `pkde/<family>-<variant>`, Categories `pkde-<family>`, Keywords, Description) and `echo \App\pattern_section('<role>', '<solid|light>', <<<'HTML' ... HTML);`. `tests/Feature/PatternBankTest.php` checks the name, the family, the blocks used, their attributes against `block.json`, the lock and the content rules.
