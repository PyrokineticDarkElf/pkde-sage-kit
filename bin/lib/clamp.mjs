/**
 * Line clamp tokens: how many lines the title and the text of a kit card may take, declared in config/brand-palette.json under `text.clamp`:
 *
 *   "text": { "clamp": { "title": 2, "excerpt": 3, "product-excerpt": 4, "quote": "none", "feature-text": 4 } }
 *
 * `title` and `excerpt` are the two site-wide tokens (--pkde-clamp-title, default 2; --pkde-clamp-excerpt, default 3); the others override one kind of card
 * (resources/css/clamp.css lists them). A value is a whole number of lines from 1 to 12, or "none" for no clamp. Only what is declared is written, so a palette
 * without `text.clamp` generates exactly what it did before and the kit's defaults apply.
 *
 * The kit clamps only text it pulls in AUTOMATICALLY. Text an editor TYPED into a block is never clamped unless the site opts in with one of OPT_IN below: the
 * token is written AND the generator writes the clamp rule for that element, so a palette that does not name it changes nothing on those blocks.
 */
export const CLAMP_KEYS = [
  'title', 'excerpt', 'post-title', 'post-excerpt', 'product-title', 'product-excerpt', 'related-title', 'related-excerpt', 'author-bio',
  'quote', 'quote-review', 'tile-title', 'category-title', 'brand-title', 'recent-title',
  'feature-title', 'feature-text', 'quote-typed',
];

/** The typed text a site may opt in to clamping: token => the element it clamps. */
export const OPT_IN = {
  'feature-title': '.pkde-feature__title',
  'feature-text': '.pkde-feature__text',
  'quote-typed': '.pkde-quotes:not(.pkde-quotes--reviews) .pkde-quote blockquote',
};

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

  const optedIn = entries.filter(([key, value]) => OPT_IN[key] && value !== 'none');

  return [
    '',
    '/* Line clamp tokens (text.clamp in config/brand-palette.json): how many lines the title and the text of a kit card may take; resources/css/clamp.css reads them. */',
    ':root {',
    ...entries.map(([key, value]) => `  --pkde-clamp-${key}: ${value};`),
    '}',
    ...(optedIn.length ? [
      '/* Typed text the site chose to clamp (feature-title, feature-text, quote-typed): the kit never clamps it unless asked. */',
      ...optedIn.flatMap(([key]) => [
        `${OPT_IN[key]} {`,
        '  display: -webkit-box;',
        '  overflow: hidden;',
        '  -webkit-box-orient: vertical;',
        `  -webkit-line-clamp: var(--pkde-clamp-${key});`,
        `  line-clamp: var(--pkde-clamp-${key});`,
        '}',
      ]),
    ] : []),
    '',
  ].join('\n');
}
