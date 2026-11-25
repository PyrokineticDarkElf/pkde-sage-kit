/**
 * Line clamp tokens (TPL-280): how many lines the title and the text of a kit card may take, declared in config/brand-palette.json under `text.clamp`:
 *
 *   "text": { "clamp": { "title": 2, "excerpt": 3, "product-excerpt": 4, "quote": "none" } }
 *
 * `title` and `excerpt` are the two site-wide tokens (--pkde-clamp-title, default 2; --pkde-clamp-excerpt, default 3); the others override one kind of card
 * (resources/css/clamp.css lists them). A value is a whole number of lines from 1 to 12, or "none" for no clamp. Only what is declared is written, so a palette
 * without `text.clamp` generates exactly what it did before and the kit's defaults apply.
 */
export const CLAMP_KEYS = [
  'title', 'excerpt', 'post-title', 'post-excerpt', 'product-title', 'product-excerpt', 'related-title', 'related-excerpt', 'author-bio',
  'feature-title', 'feature-text', 'quote', 'tile-title', 'category-title', 'brand-title', 'recent-title',
];

/** @returns {Record<string, string>} token name (without --pkde-clamp-) => the CSS value, in the order declared */
export function resolveClamp(text, fail) {
  if (text === undefined || text === null) return {};
  if (typeof text !== 'object' || Array.isArray(text)) fail('text must be an object: { "clamp": { "title": 2, "excerpt": 3 } }.');
  const clamp = text.clamp;
  if (clamp === undefined) return {};
  if (typeof clamp !== 'object' || clamp === null || Array.isArray(clamp)) fail('text.clamp must be an object such as { "title": 2, "excerpt": 3 }.');

  const resolved = {};
  for (const [key, value] of Object.entries(clamp)) {
    if (!CLAMP_KEYS.includes(key)) fail(`text.clamp.${key} is not a token; use ${CLAMP_KEYS.join(', ')}.`);
    if (value === 'none' || (Number.isInteger(value) && value >= 1 && value <= 12)) resolved[key] = String(value);
    else fail(`text.clamp.${key} must be a whole number of lines from 1 to 12, or "none"; got ${JSON.stringify(value)}.`);
  }

  return resolved;
}

export function buildClampCss(clamp) {
  const entries = Object.entries(clamp);
  if (entries.length === 0) return '';

  return [
    '',
    '/* Line clamp tokens (text.clamp in config/brand-palette.json): how many lines the title and the text of a kit card may take; resources/css/clamp.css reads them. */',
    ':root {',
    ...entries.map(([key, value]) => `  --pkde-clamp-${key}: ${value};`),
    '}',
    '',
  ].join('\n');
}
