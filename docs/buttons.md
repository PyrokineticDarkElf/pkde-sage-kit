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

## Type, size and padding per variant, and where each variant is used

Everything under `type` can also be set on a single variant (and on a position, below), where it wins: `font` (`display`, `sans`, `mono` or a family name), `size` (`xs`, `sm`, `md`, `lg` or a length such as `0.74rem`), `weight`, `tracking` (a length or `normal`), `case`, `lineHeight` (a number or length), `height` (a minimum height), `padding` (one to four lengths, or **`"keep"`**: the button's own padding stays, for a site whose theme.json already sets it), `radius` (`none`, `sm`, `md`, `lg`, `full` or a length), `border` (`none` or a width) and `hover` (this variant's own hover). A solid variant can set the `label` colour; `"contrast": "label"` keeps its fill on every surface (a pale grey button on white) and only checks that the label reads; `"underline": "none"` makes a text button with no rule. `"hover": "none"` means no change on hover.

**Positions (`use`).** Not every button of a site is the same variant. `use` says which variant each position draws; without it every position is primary, except Reset (text):

| Position | Buttons |
|---|---|
| `card` | the loop's Add to basket and Read more, and `.pkde-btn` in a card, a feature or a quote |
| `hero` | `.pkde-btn` in the Hero, CTA and CTA band blocks (a second button with its own Button style is not affected) |
| `product` | the single product's Add to basket |
| `filters`, `reset` | the filter form's Apply and Reset |
| `forms` | Contact Form 7's submit, the comment form, the search button |
| `cart` | the basket's and checkout's buttons (Proceed to checkout, Place order), the cart and checkout blocks, Return to shop |
| `basket` | the classic basket's own Update basket and the coupon's Apply: they follow `cart` (so `"cart": "keep"` keeps them too) unless `basket` says otherwise; second action (secondary) by default |
| `content` | the core Button blocks with the default Solid style in post and page content; they follow the declaration (primary) by design, and a site can pick another variant, or `"keep"`; a block with the Outline or Text style keeps that variant |
| `nav` | the header's `nav-cta` link |
| `other` | any other WooCommerce button (the notices' View basket, My Account's Log in and Register, ...) and `.pkde-btn` |

A position takes a variant name (`"product": "secondary"`), `"keep"` (the button is left with whatever styling it has, the system does not draw it), or an object `{ "variant": "primary", ...any of the keys above }`: a **derived** variant, resolved for every surface like the three, that starts from that variant and changes what is listed (`"card": { "variant": "primary", "size": "0.6875rem", "padding": "0 0 0.25rem" }`). A button with an explicit class (`.pkde-btn--secondary`, `.pkde-btn-alt`, `.pkde-btn--text`) or a block style (Outline, Text) keeps that variant whatever its position.

```json
"buttons": {
  "type": { "font": "display", "case": "upper", "weight": 500 },
  "hover": "colour:#84491a",
  "primary":   { "style": "text", "colour": "#353b3d", "underline": "accent", "size": "0.76rem", "tracking": "0.22em", "padding": "keep", "lineHeight": 1.2 },
  "secondary": { "style": "solid", "colour": "#353b3d", "label": "white", "font": "sans", "size": "0.74rem", "tracking": "0.2em", "radius": "full", "border": "none", "height": "3rem", "padding": "0 2rem" },
  "text":      { "style": "text", "colour": "#353b3d", "underline": "accent", "size": "0.76rem", "tracking": "0.22em", "padding": "keep", "lineHeight": 1.2 },
  "use": {
    "card": { "variant": "primary", "size": "0.6875rem", "tracking": "0.18em", "padding": "0 0 0.25rem", "lineHeight": 1 },
    "product": "secondary",
    "forms": { "variant": "secondary", "padding": "0 2.25rem" },
    "filters": "keep", "reset": "keep", "cart": "keep", "other": "keep"
  }
}
```

## Moving a site that already has its own buttons

A site that moves to `buttons` and wants its pages to stay as they are:

1. Declare the variants that reproduce its main buttons, and map the positions that use them (`card`, `product`, `forms`, `hero`, `content`).
2. Put **`"other": "keep"`** in `use`, and `"keep"` on every position whose buttons it does not want redrawn (`filters`, `reset`, `cart` (and with it `basket`), `nav`). `other` is every WooCommerce button not named above: without `keep` the notices' View basket and My Account's Log in and Register change to the declaration.
3. Delete the site's own button rules that the declaration now draws, but **keep the layout properties they carried**: `margin`, `justify-self`, `align-self`, `flex`, `width` and `min-width` of the button (for example `margin: 0` on the single product's Add to basket and `justify-self: start` on the contact form's submit): move those declarations into a rule of their own.
4. Regenerate, then compare every button on the home page, a product, a category, the basket, the checkout and the contact page at 1440 and 390 before and after.

That is tcs's: text buttons everywhere except the product page's Add to basket and the contact form's submit (a solid ink pill), the filters and the basket left as the kit has them. On tcs.test it reproduces every button, hover included.

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
