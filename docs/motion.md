# The kit's motion system (WP-103)

Subtle, consistent, never in the way of the first paint: a few named timings, one reveal on scroll, one card zoom, a hover and a press state, a smooth accordion, and a site switch.

## Timings (tokens, `resources/css/motion.css`)
| Token | Default | Used for |
|---|---|---|
| `--pkde-motion-fast` | 150ms | colour, opacity, press feedback (also Tailwind's bare `transition`, through the template's `theme.css`) |
| `--pkde-motion-base` | 250ms | hover lift, icon rotate, dots, the accordion |
| `--pkde-motion-slow` | 400ms | the reveal |
| `--pkde-zoom-duration` | 300ms | the card image zoom |
| `--pkde-ease-out` | `cubic-bezier(0.23, 1, 0.32, 1)` | movement |
| `--pkde-ease-in-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | colour and state (Tailwind's own default curve) |
| `--pkde-lift`, `--pkde-zoom-scale`, `--pkde-press`, `--pkde-reveal-distance` | -3px, 1.03, 0.98, 12px | the distances |

A site overrides any of them in its own CSS (`:root { --pkde-motion-fast: 120ms; }`). Every kit transition reads them; a category tile may still set its own `--pkde-tile-hover-duration` and `-scale`.

## Reveal on scroll
A block with the Fade up setting gets `pkde-reveal`. A small script (`resources/js/motion.js`, an IntersectionObserver, no library) lets it fade in and rise 12px once, as it first comes into view. The hidden state exists only while the script has put `js-motion` on `<html>`, so **without JavaScript the content is simply there**.
Guard rails, so it can never hide something that matters:
- the server never gives the reveal to the hero, a slider or a slide, or to the **first block** of the page content;
- the script shows at once anything already on screen when the page loads, anything above the viewport (an anchor jump), and everything left when the page is scrolled to its end, so a short last block is never stuck;
- the CSS never hides an element that holds the LCP image (`img[fetchpriority="high"]`);
- not for visitors who prefer reduced motion, not in print;
- opacity and `translate` only: nothing changes size, nothing counts toward layout shift.

## Hover and press
Movement on hover (the lift, the card image zoom) applies only for a pointer that can hover (`(hover: hover) and (pointer: fine)`) and for visitors who accept motion, so a tap never leaves a zoom stuck on a phone; keyboard focus shows the same state. A button or card pressed scales a hair (`:active`); not the checkout's full-width buttons. The card zoom is ONE rule: the image carries `pkde-zoom` and its card is a Tailwind `group`.

## The site switch: `pkde_kit/motion`
`add_filter('pkde_kit/motion', fn () => 'reduced');` in `functions.php` or the site's config, `'on'` (default), `'reduced'` (hover and press only, no reveal) or `'off'` (nothing moves). It is a class on `<html>` (`pkde-motion-on`, `-reduced`, `-off`), the same for every visitor, so the page cache is never wrong. The drift check shows it as an INFO row. The visitor's own `prefers-reduced-motion` always wins.

## In the editor
- **Administrators** (`manage_options`; filter `pkde_kit/motion_panel`) see the full **Animation** panel on every block.
- **Everyone else** sees one **Reveal on scroll** toggle on Section and the kit's section blocks, and only while the switch is `on`.
- **New Sections reveal by default**; existing Sections keep what they have (a Section saved without the setting stays as it is). Turning the toggle off stores an empty value, so it stays off.

## Other behaviour
Slider and carousel arrows and dots jump instead of sliding for visitors who prefer reduced motion; the dots animate `transform`, not `width`; no `transition: all`; accordion items, FAQ rows and the Details block open smoothly where the browser can animate to the content's height (`interpolate-size`, from tcs).

## What not to animate
The page on load, the LCP, the hero, sliders and the first section; prices, the basket and checkout; form errors; parallax or scroll-jacking; anything that loops over 5 seconds without a pause; layout properties; the product gallery (it keeps CLS 0).
