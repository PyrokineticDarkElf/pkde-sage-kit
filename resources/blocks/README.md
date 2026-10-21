# Native Gutenberg blocks

Sixteen real, working blocks ship, editable via the native block editor (RichText,
InspectorControls, MediaUpload, InnerBlocks where relevant) and rendered on the frontend by plain
PHP — no ACF or third-party block plugin needed:

| Block | What it renders | Needs WooCommerce? |
| --- | --- | --- |
| Hero | Full-width hero: heading, supporting text, optional background image, button. | No |
| Recent Posts (Grid) | Grid of the most recent blog posts, optionally filtered to one category. | No |
| Hero Slider / Image Carousel autoplay | Optional auto-advance (off by default; never under reduced motion; Pause button) - see docs/autoplay-and-modal.md. | No |
| Entry Grid | Card grid of EVERY published entry of any post type (a listing page such as Portfolio or Services, not a latest-N teaser): count 0 = all, ordered by the Order field then title, no "view all" link. | No |
| Featured Posts | Grid of posts marked "Stick to the front page", or a manually chosen set. | No |
| Product Categories | Grid of WooCommerce product categories: cards (default) or photograph tiles; optional include/exclude lists (in your order), label overrides and extra tiles that link anywhere. | Yes |
| Product Loop | A configurable product grid on the shop's own cards (the `[products]` shortcode): count, columns, order by (shop order, best selling, top rated, newest, price, name, random), direction, include and exclude categories, live editor preview. | Yes |
| Shop products | The shop's live product archive (filters, sorting, pages) where it sits on the Shop page; nothing elsewhere. | Yes |
| Brands | Card grid of WooCommerce product brands — the same grid the Brands directory page uses. | Yes |
| Recent Products | Grid of the most recently added products. | Yes |
| Best Selling Products | Grid of products ordered by total sales. | Yes |
| Featured Products | Grid of products marked "Featured", or a manually chosen set by SKU. | Yes |
| CTA | Call-to-action section — heading, text, up to two buttons. "Section" (centered, full padding) or "Banner" (compact strip) style. | No |
| Accordion | A stack of collapsible items, each its own Accordion Item block. | No |
| Accordion Item | One collapsible heading/content pair — only usable inside Accordion or FAQs. | No |
| FAQs | Same rendering as Accordion, plus JSON-LD `FAQPage` structured data. | No |
| Image Gallery | A static image grid — configurable columns, aspect ratio, and an optional lightbox. | No |
| Image Carousel | A swipeable, loopable, draggable set of images (Embla) with optional arrows/dots/lightbox. | No |
| Hero Slider | A loopable, draggable row of miniature hero sections (Embla), each its own Hero Slide block. | No |
| Hero Slide | One slide's heading, text, background image, button, and theme — only usable inside Hero Slider. | No |

The five WooCommerce-dependent blocks aren't just inert when WooCommerce isn't installed — `../../app/blocks.php`
skips registering them entirely in that case (checked against a hardcoded list, `$requires_woocommerce`),
so they don't appear in the block inserter at all on a non-store site, rather than appearing and
rendering nothing.

**Every loop block (all seven) is a complete section on its own — heading, description, and a
"View all" link, top-right, above the grid, plus its own optional Section theme.**
`heading`/`description` are real `block.json` attributes (edited via each block's own "Section
heading" Inspector Controls panel, defaulting to the same text `block-demo` used to supply
externally), and `viewAllUrl`/`viewAllLabel` are computed in each `render.php`. All seven render
through the shared `partials/blocks/section-header.blade.php` partial — one place for that layout,
not one copy per block (see "Every loop block ... can be given a Section theme" further down for
the `theme` attribute each one also carries). A client using these blocks no longer has to remember
to also place a separate Heading/Paragraph block before one for the section to have
a title at all.

`viewAllUrl` mostly reuses an existing archive as-is: Recent Posts links to its category archive (or
the blog page, with no category filter set); Brands links to the seeded Brands directory page
(`get_page_by_path('brands')`, not a hardcoded URL, so it still resolves if a client renames the
page); Recent/Best Selling Products link to the Shop page with WooCommerce's own native
`?orderby=date`/`?orderby=popularity` catalogue-ordering params — no custom query code needed for
those two. Featured Products is the one exception: WooCommerce's classic shop template has no
built-in URL filter for "featured only" (that's block-theme/shortcode-only otherwise), so
`app/woocommerce/filter-query.php` adds `?featured=1` support, reusing the exact same
`wc_get_featured_product_ids()` the block itself queries from — see that file's own comment. Product
Categories and Featured Posts don't have a truly matching archive to link to (there's no "all
categories" page, and no dedicated "featured posts" listing in WordPress), so they link to the
closest honest equivalent instead (Shop, and the blog page) — noted in each block's own `render.php`.

**`block-demo` still has its own external `<!-- wp:heading -->`/`<!-- wp:paragraph -->` before each
block that doesn't render its own heading** (see `../../../../../bin/generate-demo-content.php`,
the `wp eval-file` script `bin/setup` runs to build this page — not a static heredoc; see below) —
deliberately left in, not a leftover. It's a reference showing native heading/paragraph blocks
respecting `content-grid`'s width correctly, alongside each block's own new heading/description
doing the same thing a different way.

**The demo page's Hero/CTA/Hero Slider sections are generated, not hand-written — one themed
instance per *actual* configured palette colour.** `bin/generate-demo-content.php` reads
`../generated/theme-options.php` (itself generated by `../../bin/generate-theme-tokens.mjs` from
`config/brand-palette.json` — see `branding-tokens.md`) and loops it to build one Hero, one CTA, and
one Hero Slide per colour, alongside the fixed no-theme variant of each. A client palette with 4
colours gets 4 of each; one with 12 gets 12 — nothing here is a hardcoded count that quietly stops
matching the real palette. This replaced an earlier version of this page that hardcoded exactly 3
theme variants (`brand`, `accent`, `charlie`) regardless of how many colours the palette actually
had, the same bug the Hero/CTA block editor's own theme `<select>` had (see `branding-tokens.md`) —
both were fixed by reading from the same generated `theme-options.php`/`.js` files rather than each
maintaining its own separate hardcoded list.

**Layout bugs worth knowing about if similar symptoms show up elsewhere:**

1. **A stray 24px `margin-left` on `.woocommerce ul.products`** turned out to be
   `.prose-content ul, .prose-content ol { margin-left: 1.5em; }` (`../../resources/css/app.css`) —
   a bare descendant selector, so it matched *any* `<ul>` rendered inside `.prose-content` (see
   `partials/content-page.blade.php`/`content-page-wide.blade.php`), including this grid's own
   `<ul>`, which isn't a bulleted list at all. Fixed by scoping to direct children only
   (`.prose-content > ul`) — worth remembering if a future block's own markup happens to use a
   `<ul>`/`<ol>` and picks up spacing that clearly isn't coming from its own CSS.
2. **The product grid blocks default `count` to 4, but `loop_shop_columns` (`app/woocommerce/display.php`)
   is hardcoded to 3 site-wide** — correct for the Shop archive (which shares its row with a filter
   sidebar), wrong for these blocks (no sidebar, 4 items by default), leaving an orphaned 4th card
   alone on its own row. `.product-grid--4up` (`partials/blocks/product-grid.blade.php`'s own
   wrapper class) overrides the column count at the same breakpoints specifically for this shared
   partial, without touching the Shop archive's own (correctly different) column count.
3. **An odd item count breaks at any column tier that doesn't divide it evenly, not just 3-into-4.**
   `partials/blocks/post-grid.blade.php` (Recent Posts, Featured Posts — both default `count` to 3)
   used to go 1→2→3 columns; a 2-column tablet tier left one card alone on its own row for a 3-item
   grid. Fixed by dropping straight from 1 to 3 columns for this specific partial, with no
   intermediate 2-column tier — it's a different file from `partials/post-grid.blade.php`, which the
   blog archive uses at 12 posts per page (evenly divisible either way, so its own 2-then-3
   progression was left alone; only the blocks' smaller default count needed adjusting). This is a
   per-partial judgement call, not a rule to apply blindly — `partials/blocks/category-cards.blade.php`
   and `brand-cards.blade.php` (Product Categories, Brands) default `count` to `0` (however many
   terms exist), so no fixed column progression can guarantee no orphan for arbitrary real site
   data; it's left as-is rather than chasing a guarantee a fixed grid can't actually make.
4. **`theme.json`'s `settings.layout` only declared `contentSize` (`48rem`), with no `wideSize` at
   all** — meaning the block *editor's* own canvas-width simulation for `align:"wide"` blocks had
   nothing to size against, independent of (and inconsistent with) what the frontend's
   `content-grid`/`.alignwide` actually render at. This is a genuinely separate mechanism from the
   frontend width system above: WordPress's native editor-canvas layout is driven by
   `theme.json`'s `layout.contentSize`/`wideSize` alone, not by any of this theme's own CSS, so
   fixing the frontend never touched it. Added `"wideSize": "80rem"` to `theme.json` (the source
   file `@roots/vite-plugin`'s `wordpressThemeJson()` builds from at `vite.config.js`'s
   `wordpressThemeJson()` call — not the generated `public/build/assets/theme.json`, which a rebuild
   would just overwrite) to match `--container-content`.
5. **`wideSize` alone didn't make block-demo's own external headings look any wider — correctly.**
   `bin/generate-demo-content.php`'s heading/paragraph blocks (`<!-- wp:heading -->`/`<!-- wp:paragraph -->`
   before each loop block) were never marked `align:"wide"` in the first place, so both WordPress's
   editor and this theme's own `content-grid` correctly gave them plain `content`-width treatment —
   `wideSize` only ever applies to blocks actually marked wide. Confirmed directly in the editor
   canvas: a plain heading measured exactly `48rem` regardless of the `wideSize` fix, while a
   `sage/hero` block right next to it filled the canvas. Since block-demo's whole point is
   demonstrating the wide blocks around them, those specific demo headings now carry
   `{"align":"wide"}` (and the matching `alignwide` class on the saved HTML — Gutenberg core blocks
   bake alignment into both the block comment *and* the markup, so both need setting by hand when
   authoring raw block HTML instead of using the editor). Don't take this as "unaligned content
   should generally be wide" — that would defeat the point of `content`/`wide` being different
   widths at all; it's specific to these particular demo headings sitting deliberately alongside
   wide blocks.

**`/block-demo/`** — seeded automatically by `bin/setup` on first run (not added to the nav menu;
it's a reference/QA page, not real site content) — has one of every block above in order, so
there's always a live page to check a block against after changing its `render.php` or `edit.jsx`.
It's assigned `template-wide-content.blade.php` rather than the default page template, for the
reason below.

**Wide/full alignment and the site's shared content width.** Every block here declares
`"align": ["wide", "full"]` in its `block.json` (paired with `add_theme_support('align-wide')` in
`../../app/setup.php` — without it the editor never shows the Wide/Full toolbar buttons at all). A
content wrapper that needs to host wide/full blocks (right now: `partials/content-page.blade.php`,
`partials/content-page-wide.blade.php`) adds the `content-grid` class from `../../resources/css/app.css`
— a 3-track CSS Grid (`content` / `wide` / `full`), not a viewport-sized box with negative margins.
Direct children place themselves purely with `grid-column`: unaligned content sits in `content`,
`.alignwide` in `wide`, `.alignfull` in `full` — the classes Gutenberg core already adds per block
based on its own `align` attribute, so nothing per-block is needed beyond having a `content-grid`
ancestor. `content` and `wide` both cap at `min(<ideal-width>, 90%)` — `content`'s ideal width is
whatever that specific page wants (`content-page.blade.php`: `min(48rem, 90%)`, a prose reading
width; `content-page-wide.blade.php`: `min(80rem, 90%)`, the site's shared content width via
`theme.css`'s `--container-content` token), `wide`'s ideal width is always that same shared 80rem —
so on a page whose own content width already *is* 80rem, `wide` and `content` end up the same
width, which is correct, not a bug.

This replaced an earlier `width:100vw` + `transform:translateX(-50%)` approach — worth knowing why,
since it looked reasonable and still had two real, confirmed-not-theoretical problems:

1. **`100vw` includes the vertical scrollbar's own width** on desktop browsers with a classic
   (non-overlay) scrollbar, so an element sized to exactly `100vw` renders a few pixels *wider* than
   the actually-visible page — a permanent, unwanted horizontal scrollbar. Grid track sizing uses
   this element's own genuinely-available width, never a raw viewport unit, so the problem doesn't
   exist in the first place.
2. **Every consumer had to separately reapply matching horizontal padding** (`px-6` on one wrapper,
   `padding: 1.5rem` inside `.alignwide`) with nothing tying the two together — which is exactly how
   they drifted 24px out of sync from each other. The grid's own track/gutter sizing *is* the
   padding now: one definition (`.content-grid`'s `grid-template-columns` plus its
   `padding-inline: 1.5rem` on non-full children), not several copies that can quietly diverge.

**A fixed pixel/rem minimum on the outer gutter tracks doesn't compose safely with a percentage
cap — confirmed as real overflow, not just theoretical.** An earlier version of this grid gave the
outer gutter tracks a `minmax(1.5rem, 1fr)` floor *in addition to* the `min(<ideal>, 90%)` content
cap. At a 375px viewport, `min(80rem, 90%)` alone is already 337.5px; add a *guaranteed* 1.5rem on
each side on top of that and the total (385.5px) no longer fits in 375px — `document.documentElement.scrollWidth`
measurably exceeded `clientWidth`, a genuine horizontal scrollbar, not a rounding artifact. Fixed by
using `minmax(0, 1fr)` for the outer gutters instead: the 90% cap is already the full safety net (it
*is* a 5%-per-side gutter at any size), and a `0` floor means the gutter tracks just take whatever
space is actually left over — the total can never exceed the container's own width, because there's
no fixed floor left to conflict with anything. If you're tempted to add a minimum back for some other
reason, run the same check (`document.documentElement.scrollWidth > document.documentElement.clientWidth`)
at a genuinely narrow viewport before trusting it, not just at desktop widths where the conflict
never shows up.

**An `alignfull` block's background should reach the viewport edge, but its own content shouldn't**
— it should still line up with the `content`/`wide` column every other section's content uses (see
the Hero block's inner `mx-auto w-full max-w-content px-6` wrapper, one level in from the block's
own full-bleed root). `max-w-content`/`w-content` (from `theme.css`'s `--container-content` token,
the same value the `wide` grid track caps at) is the right tool for this kind of simple single-level
centering — it doesn't need a breakout grid of its own, since it isn't escaping a narrower ancestor
the way `content-grid`'s children are. The `px-6` on that inner wrapper matters as much as the
`max-w-content` itself: Hero's *outer* full-bleed root carries only vertical padding
(`py-10 sm:py-14 lg:py-16`) now, not the horizontal `px-6 sm:px-10 lg:px-16` scale it used to — that
scale doubled as this block's own centering math, which put its content at a different X position
than every other section on the page (confirmed directly: 24px left of page-header.blade.php's
text on an otherwise identical page, at every viewport tested). Horizontal spacing lives solely on
the inner wrapper now, as a flat `1.5rem` matching everything else — if you're tempted to add
responsive horizontal padding back onto a full-bleed block's outer root for visual effect, put it on
the inner content wrapper instead, or it'll quietly drift out of alignment with the rest of the page
again.

**Which page template to use is a real choice, not just page vs. post.** The default page template
(`page.blade.php` → `partials/content-page.blade.php`) uses a narrow `min(48rem, 90%)` prose
column — correct for genuinely long-form body text (readable line length), wrong for a page that's
mostly wide/full blocks with short section labels, where that narrow column just fights every
block's own alignment instead of helping. `template-wide-content.blade.php` (`Template Name: Wide
Content` in the editor's Page Attributes panel) swaps in `partials/content-page-wide.blade.php`
instead — same `the_content()` output, the site's shared 80rem content width in place of the 48rem
prose width. Pick whichever actually matches a given page's content shape.

**Not every place `the_content()` renders is wrapped in `content-grid` yet** —
`partials/content-single.blade.php` (single blog posts) deliberately isn't: its body sits inside an
8-of-12-column area alongside a sidebar (`partials/sidebar-single.blade.php`), and "break out to the
viewport edge" doesn't have one obvious answer when a sidebar occupies the same vertical space (push
it down? overlay it? that's a real design decision, not implied by fixing block-demo's layout). A
wide/full block placed in a blog post's body today renders as an ordinary same-width block, not
broken, just inert — worth knowing if a client ever actually wants that combination.

**Accordion, FAQs, and Accordion Item share one visual component, not two separate
implementations** — following `docs/planning/block-plan.md`'s own recommendation (§9, "FAQ"). Both
`accordion/render.php` and `faq/render.php` render the exact same markup shape (an optional
heading/description, then whatever `accordion-item/` children are placed inside); the only real
difference is `faq/render.php` additionally reads each child's parsed `heading` attribute plus its
own inner blocks (rendered separately, in isolation — rendering the *whole* child would duplicate
the question inside its own answer text, confirmed directly by generating the schema once and
seeing exactly that) to emit a `FAQPage` JSON-LD `<script>` in `wp_footer`. `accordion-item/`
declares `"parent": ["sage/accordion", "sage/faq"]` in its `block.json`, so it can only ever be
inserted inside one of those two, never on its own. The `<details>`/`<summary>` HTML elements do
the actual collapse/expand — no JavaScript at all, and correct keyboard/screen-reader behaviour for
free.

**CTA covers both of `docs/planning/block-ideas.md`'s original "Banner CTA" and "Section CTA"**
with one block and a `style` attribute (`section`/`banner`) — they had identical fields and only
differed in layout, so two separate blocks would have meant maintaining the same attribute set
twice. Colour uses the same `theme-{name}`/`theme-{alias}` system as Hero (see
`hero/render.php`'s own comment) — client-facing names like "Brand"/"Accent" rather than a raw
`theme-mps-turbo-red`-style class, which is exactly the "developer terminology vs. client
terminology" distinction block-plan.md calls out (§3) as something to design in from the start.

**Image Carousel and Hero Slider run on Embla (`embla-carousel`), not hand-rolled CSS scroll-snap
or Slick/Swiper.** The first version of these two blocks used native `overflow-x-auto` +
`scroll-snap-*` with a small hand-written controller for the arrow buttons — that held up for basic
swipe/snap, but had no real infinite loop (only `scrollIntoView` to the nearest/first/last slide,
which visibly snaps back rather than continuing round), imprecise jump distance at partial-width
slide sizes, and no drag beyond whatever the browser's native scroll gave for free. `docs/planning/block-plan.md`
(§7, §10) originally scoped these against Slick specifically, which was rejected for being a
jQuery-era dependency unmaintained since 2018; Embla is dependency-free, ~6kb, actively maintained,
and built specifically for loop/drag/precise-snap, so it's the one actually installed
(`web/app/themes/sage/package.json`). `../js/carousel.js` is now just wiring: it attaches
`EmblaCarousel()` to each `[data-carousel-viewport]` (reading `loop: true/false` off the root's own
`data-carousel-loop` attribute, set from each block's `loop` toggle) and connects the
server-rendered prev/next buttons and pagination dots to Embla's own `scrollPrev()`/`scrollNext()`/
`scrollTo()`/`selectedScrollSnap()` API — both blocks share the one controller, keyed off
`[data-carousel]`. Embla needs a specific two-level structure to do its own transform-based
positioning: `[data-carousel-viewport]` (`overflow: hidden`) wrapping `[data-carousel-track]`
(`display: flex`, the slides) — not the single-`overflow-x-auto`-div shape the old scroll-snap
version used, so both render.php files carry the extra viewport wrapper on purpose.

**Both carousel blocks have a click-to-enlarge lightbox** (`enableLightbox`, Carousel and Gallery
only — Hero Slider isn't an image gallery, so it doesn't get one) — `../js/lightbox.js`, a separate
hand-rolled (no library, no Embla involvement) overlay: one shared overlay element built once and
reused for every lightbox-enabled block on the page. Each trigger button carries
`data-lightbox-src`/`-alt`/`-group`; `-group` is a per-block-instance id from `wp_unique_id()` in
each render.php, so Prev/Next inside an open lightbox cycles only within the block instance that was
clicked, never across separate Gallery/Carousel blocks on the same page. Esc/backdrop-click/close
button all close it; focus returns to whichever trigger opened it.

**Neither carousel block spaces its slides with a CSS `gap`** — Embla's own slide-to-slide distance
math is driven by each slide element's own measured box, and `gap` isn't part of that, so a
`gap`-spaced track and Embla's idea of "one slide's width" would silently drift apart, clipping part
of the incoming slide on every scroll (this was a real, reported bug in the first Embla version of
this block, not a theoretical one). `carousel/render.php` uses Embla's own documented fix instead: a
negative margin on the track (`-ml-4`) plus matching padding on every slide (`pl-4`) — spacing that's
genuinely part of each slide's own measured box, so Embla's math and the visible gap always agree.
`hero-slider/render.php` doesn't need this at all — its slides are full width with no gap between
them by design (see below).

**Hero Slide reuses Hero's exact visual markup and theme system, minus rounded corners** —
`hero-slide/render.php` is the same background-image/gradient/heading/button structure as
`hero/render.php` (see that file's own comment for the padding/alignment rationale), minus
`rounded-4xl` (a slide sits flush against its neighbours as one continuous strip, so rounded corners
would only show, oddly, at the very first and last slide) plus `flex-none` so it behaves as an Embla
track item once `hero-slider/render.php` wraps it. `hero-slide/edit.jsx` imports the same generated
`THEME_OPTIONS` as `hero/edit.jsx` and `cta/edit.jsx` (see `branding-tokens.md`) rather than
hardcoding its own list. `sage/hero-slider` itself carries no attributes beyond
`showArrows`/`showDots`/`loop` — it's purely a themeable InnerBlocks container, the same
relationship Accordion/FAQs have to Accordion Item. Its pagination dots are absolutely positioned
over the bottom of the slide itself (`pointer-events-none` on their wrapper, `pointer-events-auto`
on each dot, so the empty space between dots doesn't block dragging/clicking the slide underneath),
not laid out below it in normal flow — a slide is a full hero section, not a small thumbnail, so
"below the slide" would just be a lot of empty page before the next section.

**Image Gallery's `columns` is a number (2–6), but the actual grid class is chosen through a PHP
`match()`, not string interpolation** — `gallery/render.php` never writes `"lg:grid-cols-{$columns}"`
directly. Tailwind's build only generates a utility class it can find as *literal* text in a
scanned source file; a runtime-interpolated string is invisible to that scanner, so the class would
exist in the HTML but have no CSS behind it. The `match()` statement gives the scanner every
possible literal class once, so whichever one actually gets used at runtime already has real CSS
generated for it. The same trick applies to `aspectRatio`. Reach for this whenever a block attribute
needs to drive a Tailwind class dynamically — the failure mode (class applied, no visible effect) is
easy to miss if you're not specifically looking for it, since nothing errors.

**Every loop block (all seven) can be given a Section theme, the same `theme-{name}` system Hero/CTA
use, via `App\pkde_loop_theme_classes()` (`app/blocks.php`) — and unlike Hero/CTA, it's always a
full-bleed coloured strip, never a padded, rounded floating panel.** A themed loop block gets a
second class, `pkde-loop-panel`, alongside `pkde-section theme-{name}` — `app.css`'s
`.content-grid > .pkde-loop-panel { grid-column: full; }` forces it into the full-bleed grid track
regardless of whatever align (wide/full) the block's own toolbar setting actually is (see that rule's
own comment for why this is a dedicated class rather than the block also carrying a literal
`alignfull` class — the two would otherwise leave two equal-specificity content-grid rules on the
same element, decided only by CSS source order). The heading/description/card grid inside still
needs to line up with the rest of the site's content column despite that unbounded background —
`App\pkde_loop_theme_content_open()`/`_content_close()` wrap them in the same
`mx-auto w-full max-w-content px-6` pattern Hero's own inner wrapper uses, only when actually themed
(an unthemed loop block gets the same inset for free from content-grid's own padding rule instead).

A background colour on its own isn't enough for contrast, either — `partials/blocks/section-header.blade.php`'s
heading/description/"View all" link, and the post/product cards two of the seven loop blocks render
(`partials/content.blade.php`, `woocommerce/content-product.blade.php`) are all designed for a plain
white page — dropped directly onto a saturated theme colour with no further changes, that text and
those cards would have poor-to-no contrast. `section-header.blade.php` takes a `$themed` prop and
switches its own three elements to `--pkde-theme-content`/`--pkde-theme-highlight` (the same tokens
Hero/CTA's foreground already reads) instead of its default neutral palette. The post/product cards
are handled differently, in `app.css`, not per-template: `.pkde-section :where(article,
.woocommerce ul.products > .product)` gives whichever of those two shapes a themed loop block
actually produces a plain white "card surface" (background, padding, rounded corners, reset text
colour) — scoped entirely by the `.pkde-section` ancestor, so it's unreachable (and every card
everywhere else on the site, and Hero/CTA's own use of `.pkde-section`, stays untouched) unless a
loop block genuinely has a theme picked. Category and Brand cards (`category-cards.blade.php`,
`brand-cards.blade.php` — see below) deliberately aren't included in that CSS rule: both are already
self-contained against any background on their own (a Category card's text overlays its own image
with a built-in dark gradient; a Brand card is either a bare logo image or a name centered in its
own `bg-neutral-100` box), so the white-surface treatment would only get in the way.

**Post/product cards can also go beyond plain white, via `cardStyle` ('white'/'tinted'/'bold', the
five loop blocks that also have a `theme` attribute — not Product Categories/Brands, see above)** —
`docs/planning/themed-cards-reference.md`'s "Proposed design" fully implemented. `--pkde-card-background`/
`-content`/`-accent` (`app.css`'s `:root`, reproducing today's plain white card exactly) replace the
hardcoded `white`/`neutral-950` the card-surface rule above used to have baked in directly; a card
additionally marked `.pkde-card-tinted`/`.pkde-card-bold` picks up `buildCardThemesCss()`'s generated
`.theme-{name} .pkde-card-{style}` override instead (`bin/generate-theme-tokens.mjs`, a sibling to
`buildSectionThemesCss()`). **Tinted** is the same colour as its section at step `50` (dark neutral
text — safe for any hue, since step 50 sits at the same ~0.97 lightness regardless of hue). **Bold**
resolves that colour's own `styles.card` (`config/brand-palette.json` — see `branding-tokens.md`'s
"Section theme roles and styles") and borrows *that* colour's entire `styles` wholesale, one hop —
`styles.card` defaults to whichever of the auto-added `black`/`white` neutral colours measures better
WCAG contrast against this colour's own background (not assumed — every `styles` field on every
colour is chosen the same contrast-aware way, not just cards; see `branding-tokens.md`), but is a
completely ordinary, hand-editable field — point it at any other palette colour instead and
regenerate. Cards don't carry a `theme-{name}` class themselves, so `cardStyle` only ever does
anything paired with a `theme` also being set — the Inspector Control's own help text says as much.
Getting `cardStyle` down to the actual card element needed two different plumbing mechanisms
depending on the block: `partials/blocks/post-grid.blade.php` passes it as an ordinary Blade
`@include` prop; `partials/blocks/product-grid.blade.php` bridges it through a `$GLOBALS` entry
instead, since `wc_get_template_part()` (WooCommerce's own template loader, used for product cards)
has no `$args` parameter to pass data through in this WooCommerce version — see that file's own
comment. `partials/entry-meta.blade.php` (the date/author line under a post card's title) also reads
the same `$cardStyle` — a real, confirmed-not-theoretical contrast bug, not a defensive guess:
its original `text-neutral-500`/`text-neutral-700` is legible on a plain white card but genuinely low
contrast against a saturated `bold` card background, the same problem the title/excerpt/price text
would have had before those were switched onto `--pkde-card-content`/`-accent`.

**Category and Brand cards are two different partials, not one shared one with a mode flag** —
`category-cards.blade.php` (Product Categories block, `template-categories.blade.php`) overlays the
term's name and product count on the bottom of its own image with a dark gradient scrim, since a
category card needs the name/count to actually browse by; `brand-cards.blade.php` (Brands block,
`template-brands.blade.php`) is image-only, with the brand's name carried in the image's own `alt`
text rather than visible page text — no caption at all when there's a real logo, falling back to the
name as a centered heading *in place of* the image (not below it) when a brand has none. They used
to be one shared `term-cards.blade.php`; once the two started rendering genuinely different card
shapes rather than a shared shape with different text, keeping them in one file with branching logic
stopped being simpler than just having two small, obvious files.

**Accordion and FAQs switch between two layouts depending on item count, via `columnThreshold`
(number, default 5, both blocks)** — `partials/blocks/accordion-layout.blade.php`, shared by
`accordion/render.php` and `faq/render.php` (same visual component, see below). Below the threshold:
heading/description sit beside the item list, an even 50/50 split (`lg:grid lg:grid-cols-2`, each
side at `lg:col-span-1`).
Above it: heading/description move above the list, full width, and the list itself reflows into a
two-column newspaper-style layout (`columns-1 sm:columns-2` — capped at two even on very wide
screens, so a single column never gets narrow enough to cramp an item's own content) instead of
staying one long single-column list. `accordion-item/render.php`'s own `<details>` already carries
`border-b` (so it looks correct as a list item OR a column entry with no layout-specific styling
needed) plus `break-inside-avoid`, so one item is never visually split across a column boundary.
Both `edit.jsx` files approximate the same switch live (via
`useSelect(... getBlockOrder(clientId).length)`) so the editor doesn't look wildly different from
the frontend, but the real decision is always server-side, recounting `sage/accordion-item` children
the same way — see block-demo's own two instances (the FAQs one deliberately seeded with a lower
threshold than its item count, so both layouts have live, real content to check against).

**Accordion and FAQs also carry a `theme` attribute, same `Section theme` control as every other
themed block** — but a conditional boxed panel (Hero/CTA's own pattern: `rounded-4xl pkde-section
theme-{name} p-6 sm:p-8 lg:p-10`, only actually applied once a theme is picked), not the loop blocks'
forced-full-bleed one (`App\pkde_loop_theme_classes()`) — these are a single standalone section, not
a repeating "loop" of many cards, so a floating panel is the better fit, and unthemed they need to
look exactly as before (no padding/rounding at all), unlike Hero which is always a panel. The
heading/description/column-divider inside `accordion-layout.blade.php` take a `$themed` prop the
same way `section-header.blade.php` does for the loop blocks. Accordion Item itself can't receive
that prop — it's a genuinely separate block instance, not a template include, so there's no
PHP-level "pass a value down" available the way a Blade `@include` gets one — so it picks up the
theme entirely through CSS scoping instead: `app.css`'s `.pkde-section .wp-block-sage-accordion-item`
rule. That rule is deliberately declared **outside** `@layer components` (the only one in the whole
file that is) — Tailwind's own utility classes live in its `utilities` layer, declared *after*
`components`, so a components-layer rule loses to a conflicting utility class regardless of selector
specificity, by cascade-layer order, not specificity at all. This was a real, confirmed bug during
development (the rule silently did nothing from inside `@layer components`, despite genuinely
out-specificity-ing `text-neutral-950`/`border-neutral-200`) before landing on the unlayered fix —
unlayered CSS always wins over every layered rule, which is exactly what overriding a utility class
needs.

Use `hero/` (this directory) as the template for a new block:

1. Copy `hero/` to a new folder, rename it, and update `block.json` (`name`, `title`, `attributes`).
2. Write the editor UI in `edit.jsx`. Import `@wordpress/*` packages as normal React — these get
   rewritten to WordPress's own globals at build time (see the `@roots/vite-plugin` explanation
   in [the root README's Project structure section](../../../../../../README.md#project-structure)),
   so don't add them to `package.json`. Import the block's own `block.json` for the
   `registerBlockType` metadata argument, and use `save: () => null` — every block in this
   template is dynamic (server-rendered), not static, so there's nothing for `save` to return.
3. Write the frontend markup in `render.php`, using `$attributes`, `get_block_wrapper_attributes()`,
   and Tailwind utility classes (any file under `resources/**/*.php` is scanned by Tailwind, so
   classes used here generate correctly).
4. Add `import '../blocks/<name>/edit.jsx';` to `../../resources/js/editor.js` — this is what
   actually bundles the block's editor UI into the single editor script WordPress loads (see
   `should_load_separate_core_block_assets` in `../../app/setup.php` — on-demand per-block asset
   loading is deliberately disabled in favour of one bundled editor script).

No separate registration step is needed beyond that — `../../app/blocks.php` auto-registers every
block folder in this directory on `init` by scanning for `block.json` files.

## Hero slider: only the first slide is eager

In a Hero Slider the first slide's image is the page's LCP image (eager, `fetchpriority="high"`); every later slide
sits off-screen in the slider track and is `loading="lazy"` with `fetchpriority="low"`, so it does not compete with
the first for bandwidth (rp measured mobile Lighthouse 85 to 94-98, LCP 3.0 s to 2.3-2.6 s). The slide number comes from
`App\next_hero_slide_number()` in `app/helpers.php`, a named function with its own `static`, because a `static` inside
`hero-slide/render.php` resets for every slide. It counts per request: a second slider on the same page has its first
slide treated as a later one, which is right while only the first is above the fold.
