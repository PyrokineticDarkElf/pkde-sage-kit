# Editor fixes that apply to every site

Three small fixes to the block editor that matter on any site. The rule behind all three: the editor shows what the page prints.

## The canvas never navigates

`resources/js/editor.js` attaches a guard to the editor's canvas iframe: a click on a link (or anything inside one) and a form submit inside the canvas are `preventDefault`ed. Before, clicking a link in a Custom HTML block loaded that front-end page inside the canvas, replacing the content being edited. Clicks on ordinary content are not touched, so selecting and editing blocks works as before. The canvas iframe is created and re-created by WordPress (device preview, entering a block), so the guard is re-attached on a one-second timer; it marks the document it has already guarded.

## The editor uses the theme's font faces

`app/setup.php` (`block_editor_settings_all`) renders the same `@fonts` directive the front end prints in the page head, and adds each `<style>` block (the `@font-face` rules for the self-hosted display/sans/mono fonts from `config/brand-palette.json`) to the editor's styles. Without it the canvas used fallback fonts that differed from the page. Check: in the editor, DevTools shows the theme font as loaded in the canvas document (`document.fonts`).

## Appearance > Site footer

When the footer is one synced pattern (a reusable block, `wp_block`, with the slug `site-footer`, shared by every page), `app/setup.php` adds an **Appearance > Site footer** menu entry that opens that pattern's editor. A classic theme has no template-part screen, so there is no other obvious place to edit it. Nothing is added when no such pattern exists, so a site without one sees no change.

## Not here

The "Gap" label (the editor's "Block spacing" relabelled), editor/front-end class parity (`page-content`, `is-front-page` on the canvas) and the editor palette from `theme.json` belong to the native-blocks framework, which builds on this.
