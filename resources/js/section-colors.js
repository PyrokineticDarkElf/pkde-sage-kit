// Section themes in the editor (WP-100): inside a Group or Section styled "Brand <Label>", every block's colour picker lists the colours that
// are readable on that section FIRST (the ones its matrix uses, then any swatch with 4.5:1 on its background); the rest of the palette follows,
// so nothing is taken away, and an Administrator still has the full picker. The list per theme is generated with the palette
// (resources/js/generated/section-compat.js, from bin/generate-theme-tokens.mjs).
import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { SECTION_COMPAT } from '@generated/section-compat.js';

/** The section theme id ("brand-sage-light") of the nearest styled ancestor of a block, or null. */
export function sectionThemeOf(store, clientId) {
  for (const parentId of store.getBlockParents(clientId, true)) {
    const className = store.getBlockAttributes(parentId)?.className ?? '';
    const match = /(?:^|\s)is-style-(brand-[a-z0-9-]+)(?:\s|$)/.exec(className);

    if (match && SECTION_COMPAT[match[1]]) return match[1];
  }

  return null;
}

/** The theme palette with the colours readable on the section first; the order of the rest is kept. */
export function compatibleFirst(palette, compatible) {
  const rank = new Map(compatible.map((slug, index) => [slug, index]));

  return [...palette].sort((a, b) => (rank.has(a.slug) ? rank.get(a.slug) : Infinity) - (rank.has(b.slug) ? rank.get(b.slug) : Infinity) || 0);
}

addFilter('blockEditor.useSetting.before', 'pkde/section-compatible-colors', (result, path, clientId) => {
  if (result !== undefined || path !== 'color.palette.theme' || !clientId) return result;

  const store = select('core/block-editor');
  const theme = sectionThemeOf(store, clientId);

  if (!theme) return result;

  const palette = store.getSettings()?.__experimentalFeatures?.color?.palette?.theme;

  return Array.isArray(palette) ? compatibleFirst(palette, SECTION_COMPAT[theme]) : result;
});
