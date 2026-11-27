# The starter set of patterns

The kit ships eight block patterns, enough to build the core pages of a site (Home, About, Contact, FAQ) and a few sections to build on. They are in the Inserter under Patterns:

| Pattern | Use |
|---|---|
| Hero: Split, picture right | the top of Home |
| Feature grid: Cards | a features section on Home |
| About page | the About page |
| Contact: Beside the form | the Contact page |
| FAQ: One column | the FAQ page |
| Call to action: Split | a closing call to action |
| Testimonials: Cards | what customers say |
| Text and image | a text with a picture |

Every pattern is a Section **locked to its content** (`templateLock: contentOnly`): an editor changes the words, pictures and links, not the layout; only an Administrator can unlock it. Patterns use kit blocks and a few core blocks only, with placeholder content and no colour of their own: the Section takes a Brand theme (`is-style-brand-<colour>` as the kit registers them).

## Role classes
A Section also matches a colour **role** that is the same on every site: `is-style-role-brand`, `-accent`, `-black`, `-white`, and `-light` for the light theme (see [section-themes.md](section-themes.md)). Markup written on role classes takes the brand of whichever site it is pasted into. That is how the pattern library works.

## The rest: pkde-patterns
The other patterns (about thirty: more heroes, product and post sections, stats, logos, FAQ and contact variants) are in the private `pkde-patterns` repository with a gallery that shows each one in any site's brand. WordPress copies a pattern into the page when it is inserted, so the library is never a dependency of a site: a designer copies a pattern from the gallery and pastes it into the page. Its README explains it.

In development, `/?pkde_pattern_preview=<slug>` shows one pattern in a bare page (app/pattern-preview.php; only when `WP_ENV` is `development`).
