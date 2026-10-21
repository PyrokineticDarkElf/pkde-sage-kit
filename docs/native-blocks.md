# Pages on native blocks: the framework

A client edits a page with the block editor's own controls. Core blocks first, native settings instead of CSS classes, design defaults in `theme.json`, and one custom layout block (Section). The template keeps its `pkde-` namespace; only a site's own prefix is ever removed.

## Spacing presets and viewports (`theme.json`)

`settings.spacing.spacingSizes` replaces WordPress's generated scale (`spacingScale.steps: 0`) with named sizes, so the editor's spacing picker offers the design's values and nothing else:

| Slug | Use |
|---|---|
| `xs`, `s`, `m` | small fixed steps (0.5, 0.75, 1.5rem) |
| `l` | default gap between blocks inside a Section (fluid, up to 3rem) |
| `gap` | grid and card-list gap |
| `card` | card padding |
| `gutter` | page side margin |
| `section` | top and bottom padding of a Section |

A site changes the look by editing these values in `theme.json`, not by adding CSS. `settings.viewport` holds the two breakpoints (`mobile` 767px, `tablet` 980px) used by the editor's per-device controls and by any CSS that has to match them. `styles.spacing.blockGap` is `0` so a plain page has no hidden gaps; Sections set their own.

## The Section block (`sage/section`)

A full-width band: the background runs edge to edge, the content keeps the page width and side margin. Everything inside is core blocks. It has no settings of its own, only the native ones: background, text and link colour, border, padding, and Gap. Its defaults (padding `section` top and bottom, `gutter` at the sides, Gap `l`) live in `styles.blocks["sage/section"]` in `theme.json`, so a client changing them in the editor overrides a default and a site changing the design edits one place.

* Source: `resources/blocks/section/` (`block.json`, `edit.jsx`). Static save with `useInnerBlocksProps`, so it needs no `render.php`.
* The saved markup is `<div class="wp-block-sage-section alignfull">`. Hand-written or converted content must include `alignfull`, or the editor reports the block as invalid.

## Gap, not "Block spacing"

WordPress labels the block-gap control "Block spacing", which clients do not understand. `resources/js/editor.js` relabels it to **Gap** with an `i18n.gettext` filter.

## Animation panel

Every block gets an **Animation** panel with two choices, `On hover` (None, Lift) and `When scrolled into view` (None, Fade up).

* The choice is stored as a block attribute (`animationHover`, `animationEntrance`), never in the saved HTML. `app/blocks.php` adds the class (`pkde-hover-lift`, `pkde-entrance-fade-up`) when the page renders, so removing the feature never invalidates a saved block.
* The motion is CSS only (`resources/css/app.css`) and lives inside `@media (prefers-reduced-motion: no-preference)`. The scroll-driven entrance additionally needs `animation-timeline: view()` (`@supports`); a browser without it shows the block as normal.
* To add an option, add it to `SETTINGS` in `resources/js/animation.jsx` and write its CSS class with the same prefix.

## The editor palette is the brand's own

`vite.config.js` sets `disableTailwindColors: true`, so the editor offers the brand palette instead of Tailwind's 300-odd swatches. `bin/generate-theme-tokens.mjs` writes `settings.color.palette` in `theme.json` (the only part of that file it touches; `npm run theme:check` fails when it is stale): per colour the steps 50, 200, 400, 600, 800 and 950, the exact hex for black and white, and neutral 100, 300, 500, 700 and 900. Slugs equal the Tailwind names (`brand-600`), so `bg-brand-600` in a view and the editor's "Brand 600" swatch are the same colour. Tailwind utilities still work everywhere; only the editor picker changed.

## Editor parity

The editor must show what the page prints.

* `resources/css/site.css` is the home of a site's own design CSS. `app.css` and `editor.css` both import it, so the canvas gets it too. `resources/css/editor-canvas.css` is editor-only (dashed outlines around groups and columns); it is never printed.
* The front end wraps a page's content in `.page-content` (`partials/content-page*.blade.php`). The canvas root gets the same class for pages, and `.is-front-page` on the page set as the front page (`frontPageId` is added to the editor settings in `app/setup.php`; `resources/js/editor.js` toggles the classes). Scope a site's design to those classes and it renders the same in both places. The template's own front page is a Blade skeleton, not `the_content()`, so it does not print `.is-front-page` itself; a site that builds its home page from blocks adds the class to its wrapper.
* A click guard (`editor.js`) stops links in the canvas from navigating it.
