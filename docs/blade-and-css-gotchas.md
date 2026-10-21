# Blade / CSS / JS gotchas worth knowing before you hit them

A running list of real, confirmed problems hit while building on this template — each one is a
demonstrated bug or false alarm, not a style preference. Add to this list rather than
rediscovering one of these from scratch a second time.

## Cascade layers: a `@layer components` rule always loses to a conflicting utility class

Tailwind v4 declares its layers in a fixed order — `theme, base, components, utilities` (or
similar) — and **cascade layers are resolved before specificity**: a rule in an earlier-declared
layer (`components`) loses to a conflicting rule in a later-declared layer (`utilities`)
**regardless of selector specificity**. A components-layer selector that comfortably
out-specificities a utility class (e.g. `.pkde-section .wp-block-sage-accordion-item` vs. a bare
`text-neutral-950`) still loses, silently — no error, the override rule just does nothing.

Confirmed directly in this codebase: the Accordion Item theming rule in `resources/css/app.css`
did nothing at all while it lived inside `@layer components`, despite being far more specific than
the utility classes it was meant to override. **Fix**: declare the override rule **outside any
`@layer` block entirely** — unlayered CSS always wins over layered CSS regardless of specificity.
See that same rule's own comment in `app.css` (search for "Deliberately OUTSIDE @layer
components") for the fix in place, and the Contact Form 7 styling block right after it for a second
occurrence of the same fix against a **plugin's own unlayered stylesheet** rather than Tailwind's
utilities layer — the same rule applies to any unlayered CSS you don't control, not just Tailwind's
own layers.

Before assuming a components-layer override "isn't specific enough," check whether it's actually
losing to cascade-layer order instead — increasing specificity won't fix that, only moving the rule
out of `@layer` will.

## Blade compilation can break on two specific patterns

**Inline `@php(...)` immediately followed by another `@php`/`@endphp` block on the next line can
break Blade compilation** (a real `ParseError` at runtime, not a style preference). Fix: use
block-form `@php ... @endphp` everywhere a Blade directive might immediately follow, never the
one-line `@php(...)` idiom in that position.

**A Blade `{{-- --}}` comment containing literal `@`-directive-shaped text can also break
compilation, but only in one specific circumstance**: when a real block-form directive doing the
same thing the comment describes sits immediately next to it. Two comments elsewhere in this
project mention `@include`/`@php(...)` by name in passing and compile cleanly — this isn't a
reason to rewrite every comment that happens to reference Blade syntax. Prefer describing Blade
syntax in prose over quoting it literally in a comment, or use a plain PHP `/* */` comment inside a
real `@php` block if the literal syntax must be shown.

## `collect()->map()->filter()` on an associative array needs `->values()` before an indexed `@foreach`

```php
$items = collect($assocArray)
    ->map(fn ($label, $key) => [...])
    ->filter(fn ($item) => ...)
    ->values(); // <- without this, @foreach ($items as $index => $item) throws
```

Without `->values()`, the collection keeps the original array's string keys. `$index + 1` inside
the loop throws `TypeError: Unsupported operand types: string + int` the moment a filtered-out
earlier item makes the keys non-sequential — this only surfaces once real content actually causes
at least one item to get filtered out, so it can pass every test with placeholder data and still
break on real content.

## Flex/grid items default to `min-width: auto` — long text can overflow instead of wrap

A flex/grid item's default `min-width` is `auto`, meaning "at least as wide as my content's own
intrinsic minimum" — for a long headline at a large font size, that intrinsic minimum can be wider
than the actual available column width, and the child refuses to shrink below it regardless of how
correctly-sized the parent container is. This only bites when restructuring previously block-level
content into a flex/grid layout (a centred single-column hero into an asymmetric two-column grid,
say), so it's easy to introduce without noticing until a real narrow-viewport check.

**Fix**: add `min-width: 0` (Tailwind: `min-w-0`) to the grid/flex container and/or the specific
item holding the long text.

## A display-font swap can change what fits, even at the "same" size class

Different typefaces have meaningfully different average glyph widths — "the same font-size number"
is not "the same rendered width." A headline that fits fine on a real narrow-viewport screenshot
with one font can overflow with a different font at the exact same Tailwind size class.

**Lesson**: re-check real mobile viewports after *any* font swap, not just after layout changes.
`overflow-wrap: break-word` (Tailwind: `break-words`) is worth adding as a standing safety net on
large headline text regardless — it costs nothing when everything fits, and prevents a silent
overflow the next time a font, a translation, or longer client copy pushes past the same limit.

## A generator/build script that rewrites a shared file can silently wipe unrelated hand-edits

Historical, not current — kept for the lesson, since the specific mechanism this was originally
about no longer exists. `bin/generate-theme-tokens.mjs` used to regenerate a Google Fonts `<link>`
tag in `layouts/app.blade.php` from the palette config's own `fonts.*` fields, rebuilding that
whole query string from scratch on every run — which silently deleted any font a project had
hand-added to that same link outside the generator's own knowledge, on every unrelated
regeneration (a brand-colour change, an unrelated font swap). Fonts are self-hosted entirely
differently now (see [`branding-tokens.md`](branding-tokens.md)'s "Fonts" section) and this
generator doesn't touch `app.blade.php` at all any more, so the specific bug is moot — but the
underlying mistake (rewriting a whole shared value instead of patching only the parts actually
owned) is exactly the kind of thing worth watching for in any other generator in this codebase.

**Lesson**: a generator that regenerates a file it doesn't fully own should patch only the specific
values it actually owns, not rewrite the whole file/attribute — and after running any such script
for an unrelated reason, it's worth checking whether it touched more than you expected, rather than
assuming "I only changed the colour" means only colour-related output changed.

## A seamless CSS marquee/ticker needs its "50%" to be exactly half — a stray `gap` breaks the math

The standard seamless-scroll technique — render content twice back-to-back in one `width:
max-content` flex container, animate `transform: translateX(-50%)` — is only seamless when copy #1's
own rendered width is **exactly** 50% of the container's total width. A `gap` applied at the outer
container level (between the two duplicated copies) inflates the total width without being part of
either copy, so `-50%` overshoots copy #1's actual end position and produces a small but visible
snap/jump once per loop.

**Fix**: put spacing (gap or trailing padding) *inside* each copy only, never on the outer
container holding the two copies as its direct children. Verify numerically, not just by eye:
`copy1.getBoundingClientRect().width` must equal exactly half of
`track.getBoundingClientRect().width`. Relevant if this template ever grows a marquee/ticker-style
block — the `carousel` block's own looping, if any, is a different technique and not automatically
subject to this same gotcha.

## Headless-browser screenshots and WebGL pixel reads can both lie right after a state change

A screenshot taken **immediately** after a scroll, navigation, or DOM update can render blank or
show stale content even though the underlying DOM/CSS is completely correct — a timing/compositing
artifact of headless tooling, not a real bug. Separately, reading a WebGL canvas's pixels via
`gl.readPixels()` some time after the frame was drawn can come back empty/transparent **even when
rendering is genuinely working correctly**, because the default drawing buffer
(`preserveDrawingBuffer: false`) is allowed to clear itself between composite/present cycles.

**Lesson**: before concluding something didn't render, re-verify with a fresh screenshot after a
brief wait, or check the DOM/computed styles directly (`getComputedStyle`, `read_page`/accessibility
tree) rather than a screenshot alone. Don't treat a single screenshot or an out-of-band
`readPixels()` snapshot as ground truth for whether something is actually rendering.

## A brand-new Blade/PHP view is styled only after the node container restarts

**Symptom**: you create a new view (or block template) that uses Tailwind classes no other file uses, reload, and it
renders unstyled, as if Tailwind did not exist. Found when a coming-soon view was added on a real site.

**Cause**: Tailwind v4 scans the files named by `@source` in `app.css` for class names, and in `npm run dev` Vite's
file watcher is what tells it about a new file. On a Docker bind mount the watcher does not reliably report files
created after the dev server started, so the new file's classes are never generated.

**Fix**: `docker compose restart node` (or edit and save `resources/css/app.css`) after creating a new view, then
reload. A production build (`npm run build`) scans everything and is not affected.
