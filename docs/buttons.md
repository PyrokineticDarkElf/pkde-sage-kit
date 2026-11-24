# Buttons: one declaration, every button (`buttons` in `config/brand-palette.json`)

A site declares its buttons **once**, next to its colours; the generator resolves each of the three variants for every surface (every Brand section theme in light and dark, every card, the page and the older `.theme-*` sections), and every button of the site is drawn from the result: the kit's blocks, WooCommerce, Contact Form 7 and the core Button block. A button is the same brand button in a section and in a card, with only the colours adjusted where a surface needs it for contrast.

```json
"buttons": {
  "primary":   { "style": "solid",   "colour": "accent" },
  "secondary": { "style": "outline", "colour": "black" },
  "text":      { "style": "text",    "colour": "black", "underline": "accent" },
  "type":      { "case": "upper", "weight": 600, "tracking": "0.12em", "size": "sm" },
  "hover":     "darken"
}
```

- **Variants.** `primary` is the default button everywhere, `secondary` the second action, `text` the inline "Read more" kind. Each has a `style`: `solid` (filled), `outline` (an edge, no fill) or `text` (no fill or edge, an underline of `underline` or, without it, the `colour`). Colours are palette references: a name (`accent`), `<name>-<step>` (`accent-700`), `<name>-exact`, or a hex (`#353b3d`, not substituted). Anything left out takes the kit's default: a solid primary in the accent, an outline secondary and a text button in the palette's black, the underline in the accent.
- **The shape** (radius, border width, the underline's thickness `--pkde-button-underline-width`) comes from the [shape tokens](branding-tokens.md#shape-corner-radius-and-border-thickness-shape); a text button has no radius.
- **`type`** applies to every button: `case` (`upper`, `lower`, `capitalise`, `none`), `weight` (100 to 900), `tracking` (a length), `size` (`xs`, `sm`, `md`, `lg`). Leave a key out to keep the button's own.
- **`hover`:** `darken` or `lighten` (one step of the colour's ladder), `invert` (solid and outline swap), or `colour:<palette name or hex>` (the fill of a solid button, the label and edge of the others).

## What the generator does for each surface

For a surface (say a Brand Sage section) it works out, per variant, the fill, the label, the edge and the hover of each. If a colour fails against the surface the nearest step of the same hue that passes is used, and `npm run theme:check` lists what was substituted ("Buttons: the primary button fill accent fails contrast, accent-700 used, on 3 surfaces"):

| Part | Needs |
|---|---|
| a solid fill | 3:1 against the surface, and the label (the palette's white or black) 4.5:1 on it |
| an outline label and edge | 4.5:1 against the surface |
| a text label | 4.5:1 against the surface |
| a text underline | 1.5:1 (it decorates a label that already passes) |
| a hover colour | the same as the part it changes |

The output is CSS variables on each surface: `--btn-primary-bg`, `-text`, `-border`, `-hover-bg`, `-hover-text`, `-hover-border`, and the same for `secondary` and `text`; a card replaces them with its own surface's (`--btn-card-*` become `--btn-*` inside `article`, a product, a testimonial or a feature card). The section and card button roles (`--section-button-*`, `--section-card-button-*`) are **outputs** of this too.

## Every button uses it

- The kit's buttons carry `.pkde-btn` (primary), `.pkde-btn-alt` or `.pkde-btn--secondary`, and `.pkde-btn--text`. The Hero and CTA blocks have a **Button style** (primary, secondary, text) for each button; `App\button_classes('text')` is the helper for a view.
- The core **Button block** has the block styles **Solid** (the default), **Outline** and **Text**. An Editor picks the style only (no colour, size, radius or spacing controls); an Administrator keeps all of them.
- WooCommerce's buttons (loop and single add to cart, basket, checkout, place order, the cart and checkout blocks, coupon and update-basket), Contact Form 7's submit, the comment form, the filter Apply (primary) and Reset (text) and the header's `nav-cta` link are drawn from the same variables. Controls that are not brand buttons (the carousel arrows, the view toggle, the pagination, the accordion triggers, the header icons) keep their own look.
- The rules are generated into `generated/section-themes.css` and stand one level above the older per-component rules (no `!important`). A site's own CSS for a button, such as tcs's `.product-card__action a`, should be deleted once the declaration gives the same look.

## Backward compatibility

A palette **without `buttons`** generates nothing new and renders exactly as before: every colour, edge, radius and font of every button was compared on the template stack (home, about, contact, block demo, shop, category, product and basket at 1440 and 390). The existing roles stay as **per-surface overrides** and win over the declaration where a colour declares them (`buttonBg`, `buttonText`, `buttonBorder`, `buttonRing`, `buttonHoverText`, `buttonStyle`, and `card.button*`): a site that has them keeps its look and moves to `buttons` at its own pace, deleting the overrides when the look matches.
