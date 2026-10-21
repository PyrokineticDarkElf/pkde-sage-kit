# Slider and carousel autoplay, and the generic modal

## Autoplay on the Hero Slider and Image Carousel blocks

Both blocks have an **Autoplay** panel: "Auto-advance slides" (off by default, so existing pages are unchanged) and a delay of 2-15 seconds (default 5). The behaviour lives in `resources/js/carousel.js` (Embla's `embla-carousel-autoplay`, pinned at 8.6.0) and follows WCAG 2.2.2 / 2.3.3:

* **Never moves for a visitor who prefers reduced motion** (`prefers-reduced-motion: reduce`): the autoplay plugin is not started and the Pause button is removed.
* **Pauses** while the pointer is over the slider or focus is inside it, and while the browser tab is hidden. Dragging or clicking a dot does not cancel it for good.
* **A Pause/Start button** (bottom left, `data-carousel-autoplay-toggle`, with `aria-pressed`) is rendered whenever autoplay is on, so a visitor can stop and restart the movement.
* Only sliders with at least two slides autoplay.

Markup contract (set by each block's `render.php`): `data-carousel-autoplay="true|false"` and `data-carousel-autoplay-delay="<ms>"` on the `[data-carousel]` root.

## Generic modal

`resources/js/app.js` provides a small modal for any markup:

```html
<button type="button" data-modal-trigger="notify">Notify me</button>
<div id="notify" role="dialog" aria-modal="true" aria-label="Notify me"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50">
  <div class="bg-white p-6">
    <input type="email" aria-label="Email">
    <button type="button" data-modal-close>Close</button>
  </div>
</div>
```

* A `[data-modal-trigger="id"]` opens `#id`. A `[data-modal-close]` inside it, a click on the backdrop (the `#id` element itself) or **Escape** closes it.
* Opening moves focus to the first field, link or button inside the dialog and stops the page behind from scrolling; closing returns focus to the trigger.
* The script toggles `hidden` and `flex` together, so the container needs `fixed inset-0 items-center justify-center` and `hidden` initially. The dialog role, `aria-modal` and an accessible name are the site's markup to provide.
* It does not trap Tab inside the dialog; a modal with many controls should use the native `<dialog>` element instead.

Checked in a real browser (Chrome via puppeteer, reduced motion emulated): autoplay advances after the delay, the Pause button stops and restarts it, reduced motion leaves the slider still and removes the button, the modal opens with focus on its first field and closes with Escape, the close button and a backdrop click with focus returning to the trigger.

## Hero Slide heading level (one real `<h1>` per slider home page)

A page built from a Hero Slider has no page header of its own, so without help it has no `<h1>`. Each **Hero Slide** now has a **Heading level** setting (H1 to H4, default H2): set exactly one slide, usually the first, to H1 and leave the others on H2. The editor shows the chosen tag, and an invalid stored value falls back to H2 on the front end. Checked locally: a slide set to `h1` prints `<h1>`, the default prints `<h2>`, a bogus value prints `<h2>`.

## Menu call-to-action button (`.nav-cta`)

Give one menu item the CSS class `nav-cta` (Appearance > Menus; enable "CSS Classes" under Screen Options) and it renders as a solid brand-600 pill with white text in the desktop and the mobile menu (hover: brand-700). The rule is in `resources/css/app.css`, outside `@layer components` so it wins over the label span's own hover utility; it targets the two menu ids only. Checked in Chrome at desktop and mobile width: the item gets the button, the label stays white on hover, other items are unaffected.
