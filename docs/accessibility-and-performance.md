# Accessibility and render-blocking CSS

Small switches and fixes that apply to every site.

## Mobile menu: Escape closes it and returns focus

`resources/js/app.js` listens for Escape while the menu (`[data-nav-toggle]`) is open: it closes the panel, sets
`aria-expanded="false"` and puts focus back on the toggle button. Checked in a real browser: click the toggle, Tab into the
menu, press Escape, the menu closes and the button has focus.

## Search box keeps the browser focus ring

`resources/views/forms/search.blade.php` no longer removes the outline (`focus:outline-none` is gone), so keyboard users see
the browser's focus ring on the search field; the border colour change stays.

## The decorative 404

The large "404" on `404.blade.php` is `aria-hidden="true"`: it is decoration, the heading below it says "Page not found".

## Opt-in: drop WordPress's block stylesheets on Blade-only sites

A site whose pages are built from Blade templates (not block content) can skip `wp-block-library`, `global-styles` and
`classic-theme-styles` (about 165 KB of render-blocking CSS). Off by default, so block-built sites are unaffected. Turn it on
in the site's own code (for example its own file under `app/`):

```php
add_filter('pkde_dequeue_block_styles', '__return_true');
```

It runs on the front end only (`wp` hook). Return `false` again if a page ever uses block content. The switch lives in
`app/filters.php`.

## Not needed: a theme image glob

cs added `import.meta.glob(['../images/**'])` so `Vite::asset()` could find theme images. The template does not need it:
`vite.config.js` already passes `assets: ['resources/images/**', 'resources/fonts/**']` to the Laravel plugin, and a build
with an unreferenced image in `resources/images/` puts it in `manifest.json` (verified). If a site still cannot resolve a
theme image with `Vite::asset()`, check that its `vite.config.js` has that `assets` line.
