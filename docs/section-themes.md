# Section themes

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
| `--section-link` | links in running text: a palette colour that reaches 4.5:1 on the background and 3:1 against the text colour where one exists; otherwise the text colour, and those links are underlined (see below) |
| `--section-button-bg` / `-button-text` | the filled button and its label |
| `--section-button-border` | the filled button's edge: its own colour (invisible) unless the button barely shows against the section (under 3:1), then its label colour |
| `--section-button-ring` | optional role `buttonRing`: the filled button's inset ring. Without it the ring follows `--section-button-border`, as before; `"transparent"` draws none |
| `--section-button-hover-text` | optional role `buttonHoverText`: the filled button's label on hover and keyboard focus. Without it the label keeps its colour. It needs 4.5:1 on the button |
| `--section-button-alt-bg` / `-button-alt-text` | the secondary button, a **true outline**: no fill (`transparent`), the text colour for the label and the edge |
| `--section-icon` | icons (`.pkde-icon`) |
| `--section-eyebrow` | the small kicker above a heading (`.pkde-eyebrow`: hero, feature grid, testimonials, text and image). The **brand highlight** (4.5:1): the first palette colour, accent then brand, whose exact value reads on the section (a solid theme leaves its own colour out), else the nearest step of that hue (200 to 800) that does; one that cannot be told from the text (under 1.5:1 against it) is skipped. A weak colour has nothing to offer, so it keeps the text colour. Declare `eyebrow` in `styles` to choose it |
| `--section-accent` | small rules, dividers and icon accents (3:1, decorative), worked out the same way; read as `--pkde-rule` in the kit's CSS. Declare `accent` to choose it |

**The accent role is read by** the core **Separator** block inside a Brand section (a Separator with its own colour keeps it; a plain `hr` keeps the border role), the `.pkde-rule` class (a short decorative rule, 3rem wide, `--pkde-rule-width` and `--pkde-rule-gap` to change it; `<hr class="pkde-rule">`), and, through the `icon` role, the kit's icons (feature items): **`icon` defaults to the accent value**, a declared `icon` still wins. The kit draws no rule on its cards today; a site's own card rule reads `--pkde-rule`, which is the card's accent inside a card layout.

**Eyebrow block style:** core **Paragraph** and **Heading** have an "Eyebrow" style in the Styles panel. It applies `.pkde-eyebrow` (small, uppercase, tracked, in `--section-eyebrow`), the same look as the eyebrow of the kit's blocks and the pattern bank, so a kicker typed as a plain paragraph or heading ("F.A.Q.") is coloured by the section like the rest.
| `--section-border` | rules and separators |

Core headings, paragraphs, lists, links, buttons, separators and the kit's blocks (hero, CTA, FAQ, feature grid, text and image, testimonials, gallery,
carousel) read them. Paragraphs, list items, table cells and quotes of core blocks take the section's text colour directly (not only by inheriting it), so a site's generic `.page-content p { color: … }` — and the editor, which puts `.page-content` on the canvas — cannot leave dark text on a dark theme; text inside a nested block with its own colour, and the kit's own `pkde-` text elements, are left alone.
A block's **own** colour choice inside the section still wins: those rules carry no `!important`, and a `has-*-color` class or an inline style beats them. The exception is the Group or Section that carries the Brand style itself: if it also has its own background or text colour (an older page, or a colour picked before the style), the theme wins on that block, so the text is never set for one background on another; the block's settings show a warning with a button that removes the block's own colour.
Kit blocks that manage their own colours (`.pkde-section`: hero overlay, CTA panel, FAQ, loop panels), cards and WooCommerce products are left out of the
descendant rules. A site's own CSS that colours a heading, paragraph or button at a higher specificity still wins; move it onto the variables.

## Cards

Each theme also sets card roles for the cards inside it: `--section-card-bg`, `-card-heading`, `-card-text`, `-card-muted`, `-card-border`, `-card-button-bg`, `-card-button-text`,
`-card-button-border`, `-card-icon`, `-card-eyebrow`, `-card-accent`. Worked out from the palette unless declared: the card is **white** on a solid or tinted section (a sage section and a blush section both get
white cards), the **brand colour's lightest tint** on a white section, a deep shade in dark mode; its text, icon and border follow from that background, and its button is the first
brand colour that reaches 4.5:1 on it that is not the card's own hue (so a white card gets a sage or pink button). Declare any of them under `styles.card` (an object; the older string
`card` is the older classes' pointer): `"card": { "background": "blush-50", "buttonBg": "slate" }`; `styles.light.card`, `styles.dark.card` and `styles.light.dark.card` do the same for the other
matrices. `theme:check` covers every card pair, in both modes.

The kit's cards read them (the older `--pkde-card-*` tokens point at them inside a Brand section): post and entry cards, product cards, the testimonials, the feature grid's boxed
items. A block with cards (testimonials, feature grid, product loop, product categories, latest, featured and entry posts) has **Card colour** (Auto follows the section; or any
`Brand <Label>` theme, which re-themes only that block's cards from that theme's own colours, `has-card-theme-<theme>`) and **Card button colour** (only colours that work on the card).

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

Every theme also has a dark matrix, applied under `.dark` (the class the site's own `dark:` variants use; a site's `@custom-variant dark (&:where(.dark, .dark *))`). It is
never tied to `prefers-color-scheme`: a site without a dark switch is never flipped. A light theme's dark version uses the colour's 900 shade with light text; a
solid theme keeps its colour and re-picks its text roles by contrast.

## The contrast check

**Links.** The auto-fill gives `link` its own colour where a palette colour reaches 4.5:1 on the background and 3:1 against the text colour (so it is told apart by colour); a declared `link` always wins. Where none works (a dark solid section with white text, a theme whose text is already the palette's only readable colour) the link is the text colour, and links in running text (paragraphs, lists, table cells, quotes) are underlined, in the editor and on the page, so they are never told apart by nothing. Where the link has its own colour nothing is forced and the site's own link style applies. Buttons, cards, products and nav links are left alone. A declared link within 3:1 of the text is allowed, with a `theme:check` note, and is underlined too. The generated CSS lists only the themes that need the underline.

`npm run theme:check` fails a pair under its threshold (4.5:1 text, muted, links and eyebrows; 3:1 headings, icons and accents; the button label 4.5:1, in both modes). A colour in
`acceptLowContrast` is reported as accepted instead. Two things only warn: a button whose shape is under 3:1 against the section, and a **weak** colour: one whose best
text colour barely clears AA (under 5.2:1) while no palette colour can be a heading or link on it (a site sage, the template's accent and stone, js and cs stone). It is
offered like any other theme; its matrix uses the one readable text colour for every text role, and the check names it.

## In the editor

- Inside a themed section every block's colour picker lists the colours readable on that section first (`resources/js/section-colors.js`, from `generated/section-compat.js`).
- A Button offers only the swatches that make a button pair and text blocks only the text-safe ones (`generated/editor-palettes.php`, applied by the platform's `editor-settings` module).
- Administrators get the full picker and can unlock sections; everyone else works with the brand swatches (platform `editor-settings`).

## The aliases

`.theme-{name}` and `.theme-{alias}` and the `--pkde-theme-*` variables keep their values and also set the `--section-*` roles, so nothing changes visually.


> **One declaration for every button:** a site that declares `buttons` in its palette gets all of the button roles below as outputs, for sections and cards alike (see [buttons.md](buttons.md)). The roles stay as per-surface overrides.

## Text buttons: `buttonStyle: "underline"`

Declare `"buttonStyle": "underline"` in a colour's `styles` (the colour's own, or its `light`, `dark` and `light.dark` objects) and the filled button of that theme, and the buttons of the cards in it, become **text buttons**: no fill, no ring, no radius, the label on a rule of `--pkde-button-underline-width` (the thick border, 2px, unless a site sets the variable) in the `buttonBorder` colour (`card.buttonBorder` for a card's button). Fill the label with `buttonText`, take the fill away with `"buttonBg": "transparent"`, and give the hover colour with `buttonHoverText`:

```json
"white": { "styles": { "buttonStyle": "underline", "buttonBg": "transparent", "buttonText": "ink-950", "buttonBorder": "accent-500", "buttonHoverText": "accent-800",
                       "card": { "buttonBg": "transparent", "buttonText": "ink-950", "buttonBorder": "accent-500", "buttonHoverText": "accent-800" } } }
```

Lettering (letter spacing, weight, case) is the site's own CSS or the button's block settings; the generator only draws the underline. A palette that declares none of `buttonRing`, `buttonHoverText` or `buttonStyle` generates exactly what it did before.

## Role classes
A section also matches a **role class** that is the same on every site: `is-style-role-brand`, `-accent`, `-black`, `-white`, and `-light` for the light theme of the first three (white has none). The generated rule of each theme lists both classes (`.is-style-brand-sage, .is-style-role-brand`), so markup copied from one site takes the brand of the site it lands on; the kit's descendant rules key on either class. A colour with no role (a palette's extra colours) has no role class. The role classes are not block styles: the Styles panel is unchanged.
