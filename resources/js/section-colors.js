// Section themes in the editor: inside a Group or Section styled "Brand <Label>", every block's colour picker lists the colours that
// are readable on that section FIRST (the ones its matrix uses, then any swatch with 4.5:1 on its background); the rest of the palette follows,
// so nothing is taken away, and an Administrator still has the full picker. The list per theme is generated with the palette
// (resources/js/generated/section-compat.js, from bin/generate-theme-tokens.mjs).
import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { SECTION_COMPAT } from '@generated/section-compat.js';
import { sectionThemeOf, compatibleFirst } from './section-colors-lib.js';

addFilter('blockEditor.useSetting.before', 'pkde/section-compatible-colors', (result, path, clientId) => {
  if (result !== undefined || path !== 'color.palette.theme' || !clientId) return result;

  const store = select('core/block-editor');
  const theme = sectionThemeOf(store, clientId, SECTION_COMPAT);

  if (!theme) return result;

  const palette = store.getSettings()?.__experimentalFeatures?.color?.palette?.theme;

  return Array.isArray(palette) ? compatibleFirst(palette, SECTION_COMPAT[theme]) : result;
});
