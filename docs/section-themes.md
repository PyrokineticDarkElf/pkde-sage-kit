# Section themes (WP-100)

A **section theme** colours a whole section from one choice in the Styles panel: pick "Brand Sage" or "Brand Sage (light)" on a Group or a Section and the
background, headings, text, links, buttons, icons and rules inside it follow, in the editor and on the page. There is one solid and one light theme for every
colour in `config/brand-palette.json` (white has no light one). Nothing is written by hand: `npm run theme:generate` builds them.

## Labels

Each colour in `config/brand-palette.json` may carry a `label` (`"label": "Sage"`). The **name** is the slug stored content and CSS use and never changes; the
label is what the editor shows (swatches "Sage 600", the theme picker, the Brand &lt;Label&gt; styles). No label: the name, title-cased. `bin/setup` asks for the
primary and accent labels, or reads `brand-colours.csv` (`name,label,hex[,role]`, see `brand-colours.csv.example` in the template).

## The roles

A theme sets these variables on its section; everything inside reads them (`--pkde-theme-*`, which the kit's blocks already read, point at them):

| Variable | Used for |
|---|---|
| `--section-bg` | the section's background |
| `--section-heading` | headings |
| `--section-text` | body text |
| `--section-muted` | captions, helper text (`.pkde-muted`) |
| `--section-link` | links in running text |
| `--section-button-bg` / `-button-text` | the filled button and its label |
| `--section-button-alt-bg` / `-button-alt-text` | the secondary button (the Outline style) |
| `--section-icon` | icons (`.pkde-icon`) |
| `--section-border` | rules and separators |

Core headings, paragraphs, lists, links, buttons, separators and the kit's blocks (hero, CTA, FAQ, feature grid, text and image, testimonials, gallery,
carousel) read them. A block's **own** colour choice still wins: the rules carry no `!important`, and a `has-*-color` class or an inline style beats them.
Kit blocks that manage their own colours (`.pkde-section`: hero overlay, CTA panel, FAQ, loop panels), cards and WooCommerce products are left out of the
descendant rules. A site's own CSS that colours a heading, paragraph or button at a higher specificity still wins; move it onto the variables.

## Where the values come from

For each theme, per role, the first of: what `styles` declares, then the best-contrast palette colour (worked out by the generator).

```jsonc
"styles": {
  // the solid theme "Brand Sage": any role, as a palette reference
  "heading": "slate-800", "muted": { "color": "slate-800", "opacity": 80 },
  "light":  { "background": "sage-200", "heading": "slate-800",   // the light theme (the default background is the colour's 100 step)
              "dark": { "background": "slate-950", "text": "white" } },   // the light theme in dark mode
  "dark":   { "text": "blush-50" }                                        // the solid theme in dark mode
}
```

References: a step (`sage-200`), `sage` or `sage-exact` (the colour's exact hex), `#ffffff`, `transparent`; bare `white` and `black` are the palette's own white and black.
The older keys (`content`, `highlight`, `buttonPrimary*`, `buttonSecondary*`, `card`) belong to the older `.theme-{name}` classes and are not read by the new themes,
so adopting them never turns an existing palette red.

## Dark mode

Every theme also has a dark matrix, applied under `.dark` (the class the site's own `dark:` variants use; jsd's `@custom-variant dark (&:where(.dark, .dark *))`). It is
never tied to `prefers-color-scheme`: a site without a dark switch is never flipped. A light theme's dark version uses the colour's 900 shade with light text; a
solid theme keeps its colour and re-picks its text roles by contrast.

## The contrast check

`npm run theme:check` fails a pair under its threshold (4.5:1 text, muted and links; 3:1 headings and icons; the button label 4.5:1, in both modes). A colour in
`acceptLowContrast` is reported as accepted instead. Two things only warn: a button whose shape is under 3:1 against the section, and a **weak** colour: one whose best
text colour barely clears AA (under 5.2:1) while no palette colour can be a heading or link on it (bb sage, the template's accent and stone, js and cs stone). It is
offered like any other theme; its matrix uses the one readable text colour for every text role, and the check names it.

## In the editor

- Inside a themed section every block's colour picker lists the colours readable on that section first (`resources/js/section-colors.js`, from `generated/section-compat.js`).
- A Button offers only the swatches that make a button pair and text blocks only the text-safe ones (`generated/editor-palettes.php`, applied by the platform's `editor-settings` module).
- Administrators get the full picker and can unlock sections; everyone else works with the brand swatches (platform `editor-settings`).

## The aliases

`.theme-{name}` and `.theme-{alias}` and the `--pkde-theme-*` variables keep their values and also set the `--section-*` roles, so nothing changes visually.
