/**
 * One global button system (WP-109): `buttons` in config/brand-palette.json declares the site's three buttons once,
 *
 *   "buttons": {
 *     "primary":   { "style": "solid",   "colour": "accent" },
 *     "secondary": { "style": "outline", "colour": "ink" },
 *     "text":      { "style": "text",    "colour": "ink", "underline": "accent" },
 *     "type":      { "case": "upper", "weight": 600, "tracking": "0.12em", "size": "sm" },
 *     "hover": "darken"          // darken | lighten | invert | colour:<palette name>
 *   }
 *
 * and this module resolves each variant for a surface (a section theme's background, a card's background, the page): the colours of the fill, the label and the edge, and the hover
 * of each, with a contrast substitution where the declared colour fails (3:1 for a fill or an edge against the surface, 4.5:1 for a label). Pure functions: the colour helpers
 * of the section-theme builder (ratio, the palette resolver) are passed in.
 */
import { wcagLuminance } from 'culori';
import { steps } from './color-math.mjs';

export const BUTTON_VARIANTS = ['primary', 'secondary', 'text'];
export const BUTTON_STYLES = ['solid', 'outline', 'text'];
export const HOVER_MODES = ['darken', 'lighten', 'invert'];
export const TYPE_SIZES = { xs: '0.75rem', sm: '0.875rem', md: '1rem', lg: '1.125rem' };
export const TYPE_CASES = { upper: 'uppercase', lower: 'lowercase', capitalise: 'capitalize', none: 'none' };

const THRESHOLD = { surface: 3, label: 4.5, underline: 1.5 };

/**
 * Validates the declaration and fills the defaults. `ref` helpers: `resolver.parse` knows a palette reference. The defaults are the kit's own: a solid primary in the palette's
 * accent (else brand, else the first colour), an outline secondary and a text button in the palette's black, the underline in the primary's colour.
 * @returns {null | { primary, secondary, text, type, hover }} null when the site declares nothing
 */
export function resolveButtonsDeclaration(buttons, { resolver, colors }, fail) {
  if (buttons === undefined || buttons === null) return null;
  if (typeof buttons !== 'object' || Array.isArray(buttons)) fail('buttons must be an object: { "primary": {...}, "secondary": {...}, "text": {...}, "type": {...}, "hover": "darken" }.');

  const known = new Set([...BUTTON_VARIANTS, 'type', 'hover', 'card']);
  for (const key of Object.keys(buttons)) if (!known.has(key)) fail(`buttons.${key} is not a setting; use ${[...known].join(', ')}.`);

  const accent = colors.find((c) => c.role === 'accent') ?? colors.find((c) => c.role === 'brand') ?? colors.find((c) => c.role !== 'white' && c.role !== 'black') ?? colors[0];
  const ink = colors.find((c) => c.role === 'black') ?? colors.find((c) => c.role === 'brand') ?? colors[0];
  const defaults = {
    primary: { style: 'solid', colour: accent.name },
    secondary: { style: 'outline', colour: ink.name },
    text: { style: 'text', colour: ink.name, underline: accent.name },
  };
  const out = {};

  for (const variant of BUTTON_VARIANTS) {
    const given = buttons[variant] ?? {};
    if (typeof given !== 'object' || Array.isArray(given)) fail(`buttons.${variant} must be an object: { "style": "solid", "colour": "accent" }.`);
    for (const key of Object.keys(given)) if (!['style', 'colour', 'underline'].includes(key)) fail(`buttons.${variant}.${key} is not a setting; use style, colour, underline.`);
    const merged = { ...defaults[variant], ...given };
    if (!BUTTON_STYLES.includes(merged.style)) fail(`buttons.${variant}.style must be one of ${BUTTON_STYLES.join(', ')}; got ${JSON.stringify(merged.style)}.`);
    for (const key of ['colour', 'underline']) {
      if (merged[key] !== undefined && !resolver.parse(merged[key])) fail(`buttons.${variant}.${key} is not a known colour reference (${JSON.stringify(merged[key])}); use a palette name, "<name>-<step>" or "<name>-exact".`);
    }
    if (merged.underline !== undefined && merged.style !== 'text') fail(`buttons.${variant}.underline only applies to "style": "text".`);
    out[variant] = merged;
  }

  const type = buttons.type ?? {};
  if (typeof type !== 'object' || Array.isArray(type)) fail('buttons.type must be an object: { "case": "upper", "weight": 600, "tracking": "0.12em", "size": "sm" }.');
  for (const key of Object.keys(type)) if (!['case', 'weight', 'tracking', 'size'].includes(key)) fail(`buttons.type.${key} is not a setting; use case, weight, tracking, size.`);
  if (type.case !== undefined && !(type.case in TYPE_CASES)) fail(`buttons.type.case must be one of ${Object.keys(TYPE_CASES).join(', ')}; got ${JSON.stringify(type.case)}.`);
  if (type.weight !== undefined && !(Number.isInteger(type.weight) && type.weight >= 100 && type.weight <= 900)) fail(`buttons.type.weight must be a whole number from 100 to 900; got ${JSON.stringify(type.weight)}.`);
  if (type.tracking !== undefined && !/^-?\d*\.?\d+(em|px|rem)$|^0$/.test(String(type.tracking))) fail(`buttons.type.tracking must be a length such as 0.12em; got ${JSON.stringify(type.tracking)}.`);
  if (type.size !== undefined && !(type.size in TYPE_SIZES)) fail(`buttons.type.size must be one of ${Object.keys(TYPE_SIZES).join(', ')}; got ${JSON.stringify(type.size)}.`);
  out.type = { ...type };

  const hover = buttons.hover ?? 'darken';
  const colourHover = typeof hover === 'string' && hover.startsWith('colour:') ? hover.slice(7) : null;
  if (!HOVER_MODES.includes(hover) && !(colourHover && resolver.parse(colourHover))) fail(`buttons.hover must be darken, lighten, invert or colour:<palette name>; got ${JSON.stringify(hover)}.`);
  out.hover = hover;

  return out;
}

/**
 * Resolves the three variants against one surface.
 * @param {string} surface a palette reference for the surface background (a section's, a card's, the page's)
 * @param {object} declaration the output of resolveButtonsDeclaration
 * @param {{ resolver, ratio: (fg: string, bg: string) => number|null, bestNeutral: (bg: string) => string }} helpers
 * @returns {{ primary, secondary, text, notes: string[] }} each variant { style, bg, text, border, hoverBg, hoverText, hoverBorder }; colours are palette references or 'transparent'
 */
export function resolveButtonsFor(surface, declaration, { resolver, ratio, bestNeutral }, where = 'the page') {
  const notes = [];
  const r = (a, b) => ratio(a, b) ?? 21;

  /** Candidates of the same hue as `ref`, the nearest in appearance first (the exact colour is its own step 600 at most; hex has no ladder). */
  function nearby(ref) {
    const parsed = resolver.parse(ref);
    if (!parsed || parsed.kind === 'transparent' || parsed.kind === 'hex') return [ref];
    const ladder = [`${parsed.name}-exact`, ...steps.map((step) => `${parsed.name}-${step}`)];
    return ladder.map((candidate) => ({ candidate, distance: resolver.value(candidate) ? Math.abs(Math.log(r(candidate, ref))) : 99 })).sort((a, b) => a.distance - b.distance).map((entry) => entry.candidate);
  }

  /** The first nearby colour that passes `ok`, else null. A change is noted. */
  function pass(ref, ok, what) {
    if (ok(ref)) return ref;
    const found = nearby(ref).find(ok);
    if (found) notes.push(`${where}: ${what} ${ref} fails contrast, ${found} used.`);
    return found ?? null;
  }

  /** The label on a fill: the best of the palette's white and black. */
  const labelOn = (fill) => bestNeutral(fill);

  function one(variant) {
    const { style, colour, underline } = declaration[variant];
    const label = `${variant} button`;
    if (style === 'solid') {
      const fill = pass(colour, (c) => r(c, surface) >= THRESHOLD.surface && r(labelOn(c), c) >= THRESHOLD.label, `the ${label} fill`);
      if (fill === null) {
        // nothing of this hue works on this surface: keep the declared fill, label it by contrast and draw the edge in the label colour so the shape shows
        const lab = labelOn(colour);
        notes.push(`${where}: no ${colour} step reaches ${THRESHOLD.surface}:1 against the surface and a readable label; the ${label} keeps its fill and gets a ${lab} edge.`);
        return { style, bg: colour, text: lab, border: lab };
      }
      return { style, bg: fill, text: labelOn(fill), border: fill };
    }
    const edgeAndLabel = pass(colour, (c) => r(c, surface) >= THRESHOLD.label, `the ${label} ${style === 'outline' ? 'label and edge' : 'label'}`) ?? bestNeutral(surface);
    if (style === 'outline') return { style, bg: 'transparent', text: edgeAndLabel, border: edgeAndLabel };
    // the underline is decoration under a label that is itself 4.5:1, so it only has to show (1.5:1), not carry 3:1
    const line = pass(underline ?? colour, (c) => r(c, surface) >= THRESHOLD.underline, `the ${label} underline`) ?? edgeAndLabel;
    return { style, bg: 'transparent', text: edgeAndLabel, border: line };
  }

  const hoverOf = (variant, base) => {
    const mode = declaration.hover;
    const { style, colour } = declaration[variant];
    const lighter = (ref) => nearby(ref).find((c) => resolver.value(c) && r(c, ref) >= 1.12 && luminance(c) > luminance(ref));
    const darker = (ref) => nearby(ref).find((c) => resolver.value(c) && r(c, ref) >= 1.12 && luminance(c) < luminance(ref));
    const luminance = (ref) => { const v = resolver.value(ref); return v === null ? 1 : wcagLuminance(v); };

    if (mode === 'invert') {
      if (style === 'solid') return { hoverBg: 'transparent', hoverText: base.bg, hoverBorder: base.bg };
      if (style === 'outline') { const fill = base.border; return { hoverBg: fill, hoverText: labelOn(fill), hoverBorder: fill }; }
      return { hoverBg: 'transparent', hoverText: base.border, hoverBorder: base.text };
    }

    const target = typeof mode === 'string' && mode.startsWith('colour:') ? mode.slice(7) : null;
    if (style === 'solid') {
      const fill = target ?? (mode === 'lighten' ? lighter(base.bg) : darker(base.bg)) ?? base.bg;
      const ok = pass(fill, (c) => r(labelOn(c), c) >= THRESHOLD.label && r(c, surface) >= 1, `the ${variant} button hover fill`) ?? fill;
      return { hoverBg: ok, hoverText: labelOn(ok), hoverBorder: ok };
    }
    // outline and text: the label (and the edge) change colour on hover; an outline also keeps no fill
    const colour2 = target ?? (mode === 'lighten' ? lighter(base.text) : darker(base.text)) ?? base.text;
    const ok = pass(colour2, (c) => r(c, surface) >= THRESHOLD.label, `the ${variant} button hover colour`) ?? base.text;
    return { hoverBg: 'transparent', hoverText: ok, hoverBorder: style === 'text' ? base.border : ok };
  };

  const result = {};
  for (const variant of BUTTON_VARIANTS) {
    const base = one(variant);
    result[variant] = { ...base, ...hoverOf(variant, base) };
  }
  result.notes = notes;
  return result;
}

/** resources/generated/buttons.php: the declaration for the PHP side (the core Button block's styles, the Editor's restricted controls, the drift row). Written only when the site declares `buttons`. */
export function buildButtonsPhp(declaration) {
  const row = (variant) => {
    const v = declaration[variant];
    return `    '${variant}' => ['style' => '${v.style}', 'colour' => '${v.colour}'${v.underline ? `, 'underline' => '${v.underline}'` : ''}],`;
  };
  const type = Object.entries(declaration.type).map(([key, value]) => `'${key}' => ${typeof value === 'number' ? value : `'${value}'`}`).join(', ');

  return [
    '<?php', '', '/**', ' * Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json (buttons).', ' * Do not edit directly. Read by the kit (app/buttons.php) and the drift check. Present only when the site declares `buttons`.', ' */', '',
    'return [', ...BUTTON_VARIANTS.map(row), `    'type' => [${type}],`, `    'hover' => '${declaration.hover}',`, '];', '',
  ].join('\n');
}
