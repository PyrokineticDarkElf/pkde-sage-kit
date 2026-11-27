# The kit's blocks

One folder per block: `block.json` (the attributes and what the block supports), `edit.jsx` (the editor side) and `render.php` (the front end, rendered by the server; no block saves HTML).
`app/blocks.php` registers every folder, so a new block needs no list.

| Group | Blocks |
|---|---|
| Layout | Section (the coloured band: a role class on it themes everything inside), Hero, Hero Slider and Hero Slide, Text and Image, Feature Grid and Feature Item (the People style: picture cards with a badge, name and job title in a row that scrolls, for a team), Gallery, Carousel (hidden), CTA (styles: section, banner, split, boxed, link: one bold link with a circled arrow) |
| Content | FAQ (groups kept in the FAQs screen), Accordion and Accordion Item, Quotes, Contact Form (a Contact Form 7 form by key), Site Contact, Site Hours, Site Socials (read Settings > Site details) |
| Posts | Recent Posts (Show: the most recent, the sticky posts, or every entry in the order of their Order field), Featured Posts and Entry Grid (both hidden) |
| Shop (need WooCommerce) | Product Loop (the one product grid: order, categories, or only featured products), Product Categories, Brands, Shop Products (the shop page's list), Recent, Best Selling and Featured Products (hidden) |

**Hidden** blocks have `"inserter": false`: they do not appear in the editor's inserter, but pages that already use them keep rendering until a later release removes them.

Conventions: colours come from the Section a block sits in (`is-style-role-*` and `is-style-brand-*` classes, see `docs/section-themes.md`), never from a colour in the block. A block draws its own placeholder, so a pattern needs no image. The card look of the loop blocks is a set of `--pkde-card-*` variables (`app.css`) a site can set. Server-side previews in the editor use `ServerSideRender` or `resources/js/server-preview.jsx`.

More: `docs/` (section themes, branding tokens, patterns, the shop), and each block's `block.json` for its attributes.
