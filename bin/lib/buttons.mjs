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
export const HOVER_MODES = ['darken', 'lighten', 'invert', 'none'];
export const TYPE_SIZES = { xs: '0.75rem', sm: '0.875rem', md: '1rem', lg: '1.125rem' };
export const TYPE_CASES = { upper: 'uppercase', lower: 'lowercase', capitalise: 'capitalize', none: 'none' };
export const TYPE_FONTS = { display: 'var(--font-display)', sans: 'var(--font-sans)', mono: 'var(--font-mono)' };
/** The positions a button can be in. `use` maps each to a variant (a name, or { variant, ...overrides }); without a map every one is primary except Reset (text). */
export const BUTTON_CONTEXTS = { card: 'primary', hero: 'primary', product: 'primary', filters: 'primary', reset: 'text', forms: 'primary', cart: 'primary', nav: 'primary', other: 'primary' };
const LENGTH = /^-?\d*\.?\d+(px|rem|em|%)$|^0$|^normal$/;
const SPACING = /^(-?\d*\.?\d+(px|rem|em|%)?|0)( (-?\d*\.?\d+(px|rem|em|%)?|0)){0,3}$/;
const VARIANT_KEYS = ['style', 'colour', 'underline', 'label', 'contrast', 'font', 'size', 'weight', 'tracking', 'case', 'padding', 'height', 'radius', 'border', 'lineHeight', 'hover'];

const THRESHOLD = { surface: 3, label: 4.5, underline: 1.5 };

/**
 * Validates the declaration and fills the defaults. `ref` helpers: `resolver.parse` knows a palette reference. The defaults are the kit's own: a solid primary in the palette's
 * accent (else brand, else the first colour), an outline secondary and a text button in the palette's black, the underline in the primary's colour.
 * @returns {null | { primary, secondary, text, type, hover }} null when the site declares nothing
 */
export function resolveButtonsDeclaration(buttons, { resolver, colors }, fail) {
  if (buttons === undefined || buttons === null) return null;
  if (typeof buttons !== 'object' || Array.isArray(buttons)) fail('buttons must be an object: { "primary": {...}, "secondary": {...}, "text": {...}, "type": {...}, "hover": "darken" }.');

  const known = new Set([...BUTTON_VARIANTS, 'type', 'hover', 'use']);
  for (const key of Object.keys(buttons)) if (!known.has(key)) fail(`buttons.${key} is not a setting; use ${[...known].join(', ')}.`);

  const accent = colors.find((c) => c.role === 'accent') ?? colors.find((c) => c.role === 'brand') ?? colors.find((c) => c.role !== 'white' && c.role !== 'black') ?? colors[0];
  const ink = colors.find((c) => c.role === 'black') ?? colors.find((c) => c.role === 'brand') ?? colors[0];
  const defaults = {
    primary: { style: 'solid', colour: accent.name },
    secondary: { style: 'outline', colour: ink.name },
    text: { style: 'text', colour: ink.name, underline: accent.name },
  };

  const checkVariant = (where, spec) => {
    for (const key of Object.keys(spec)) if (!VARIANT_KEYS.includes(key)) fail(`${where}.${key} is not a setting; use ${VARIANT_KEYS.join(', ')}.`);
    if (spec.style !== undefined && !BUTTON_STYLES.includes(spec.style)) fail(`${where}.style must be one of ${BUTTON_STYLES.join(', ')}; got ${JSON.stringify(spec.style)}.`);
    for (const key of ['colour', 'underline', 'label']) {
      if (spec[key] !== undefined && !(key === 'underline' && spec[key] === 'none') && !resolver.parse(spec[key])) fail(`${where}.${key} is not a known colour reference (${JSON.stringify(spec[key])}); use a palette name, "<name>-<step>", "<name>-exact" or a hex${key === 'underline' ? ', or "none"' : ''}.`);
    }
    if (spec.label !== undefined && spec.style !== undefined && spec.style !== 'solid') fail(`${where}.label only applies to "style": "solid".`);
    if (spec.contrast !== undefined && !['full', 'label'].includes(spec.contrast)) fail(`${where}.contrast must be "full" (the default: a fill needs 3:1 against the surface) or "label" (keep the colour, only the label must read); got ${JSON.stringify(spec.contrast)}.`);
    if (spec.underline !== undefined && spec.style !== undefined && spec.style !== 'text') fail(`${where}.underline only applies to "style": "text".`);
    if (spec.case !== undefined && !(spec.case in TYPE_CASES)) fail(`${where}.case must be one of ${Object.keys(TYPE_CASES).join(', ')}; got ${JSON.stringify(spec.case)}.`);
    if (spec.weight !== undefined && !(Number.isInteger(spec.weight) && spec.weight >= 100 && spec.weight <= 900)) fail(`${where}.weight must be a whole number from 100 to 900; got ${JSON.stringify(spec.weight)}.`);
    if (spec.tracking !== undefined && !LENGTH.test(String(spec.tracking))) fail(`${where}.tracking must be a length such as 0.12em; got ${JSON.stringify(spec.tracking)}.`);
    if (spec.size !== undefined && !(spec.size in TYPE_SIZES) && !LENGTH.test(String(spec.size))) fail(`${where}.size must be one of ${Object.keys(TYPE_SIZES).join(', ')} or a length such as 0.75rem; got ${JSON.stringify(spec.size)}.`);
    if (spec.font !== undefined && !(spec.font in TYPE_FONTS) && !/^[A-Za-z0-9 _'"-]{2,60}$/.test(String(spec.font))) fail(`${where}.font must be display, sans, mono or a font family name; got ${JSON.stringify(spec.font)}.`);
    if (spec.padding !== undefined && spec.padding !== 'keep' && !SPACING.test(String(spec.padding))) fail(`${where}.padding must be "keep" (the element's own) or one to four lengths such as "0.5rem 1.25rem"; got ${JSON.stringify(spec.padding)}.`);
    if (spec.border !== undefined && spec.border !== 'none' && !LENGTH.test(String(spec.border))) fail(`${where}.border must be none or a width such as 1px; got ${JSON.stringify(spec.border)}.`);
    if (spec.lineHeight !== undefined && !(typeof spec.lineHeight === 'number' && spec.lineHeight > 0) && !LENGTH.test(String(spec.lineHeight))) fail(`${where}.lineHeight must be a number such as 1.25 or a length; got ${JSON.stringify(spec.lineHeight)}.`);
    if (spec.height !== undefined && !LENGTH.test(String(spec.height))) fail(`${where}.height must be a length such as 3rem; got ${JSON.stringify(spec.height)}.`);
    if (spec.radius !== undefined && !['none', 'sm', 'md', 'lg', 'full'].includes(spec.radius) && !LENGTH.test(String(spec.radius))) fail(`${where}.radius must be none, sm, md, lg, full or a length; got ${JSON.stringify(spec.radius)}.`);
    if (spec.hover !== undefined) checkHover(`${where}.hover`, spec.hover);
  };
  const checkHover = (where, hover) => {
    const colourHover = typeof hover === 'string' && hover.startsWith('colour:') ? hover.slice(7) : null;
    if (!HOVER_MODES.includes(hover) && !(colourHover && resolver.parse(colourHover))) fail(`${where} must be darken, lighten, invert, none or colour:<palette name or hex>; got ${JSON.stringify(hover)}.`);
  };

  const type = buttons.type ?? {};
  if (typeof type !== 'object' || Array.isArray(type)) fail('buttons.type must be an object: { "font": "display", "case": "upper", "weight": 600, "tracking": "0.12em", "size": "sm" }.');
  for (const key of Object.keys(type)) if (!['font', 'case', 'weight', 'tracking', 'size'].includes(key)) fail(`buttons.type.${key} is not a setting; use font, case, weight, tracking, size.`);
  checkVariant('buttons.type', type);

  const hover = buttons.hover ?? 'darken';
  checkHover('buttons.hover', hover);

  const variants = {};
  for (const variant of BUTTON_VARIANTS) {
    const given = buttons[variant] ?? {};
    if (typeof given !== 'object' || Array.isArray(given)) fail(`buttons.${variant} must be an object: { "style": "solid", "colour": "accent" }.`);
    checkVariant(`buttons.${variant}`, given);
    const merged = { ...defaults[variant], ...given };
    if (merged.underline !== undefined && merged.style !== 'text') fail(`buttons.${variant}.underline only applies to "style": "text".`);
    if (merged.label !== undefined && merged.style !== 'solid') fail(`buttons.${variant}.label only applies to "style": "solid".`);
    variants[variant] = merged;
  }

  // the context map: a position to a variant, or to a variant with its own overrides (a derived variant, resolved for every surface like the three)
  const use = {};
  const given = buttons.use ?? {};
  if (typeof given !== 'object' || Array.isArray(given)) fail('buttons.use must be an object: { "card": "text", "product": "secondary", "forms": { "variant": "secondary", "size": "0.74rem" } }.');
  for (const [context, spec] of Object.entries(given)) {
    if (!(context in BUTTON_CONTEXTS)) fail(`buttons.use.${context} is not a position; use ${Object.keys(BUTTON_CONTEXTS).join(', ')}.`);
    if (typeof spec === 'string') {
      if (spec === 'keep') { use[context] = 'keep'; continue; }
      if (!BUTTON_VARIANTS.includes(spec)) fail(`buttons.use.${context} must be primary, secondary, text, "keep" (the button keeps its own styling) or an object with a "variant"; got ${JSON.stringify(spec)}.`);
      use[context] = spec;
      continue;
    }
    if (!spec || typeof spec !== 'object' || !BUTTON_VARIANTS.includes(spec.variant)) fail(`buttons.use.${context} must be primary, secondary, text or { "variant": "primary|secondary|text", ...overrides }.`);
    const { variant, ...overrides } = spec;
    checkVariant(`buttons.use.${context}`, overrides);
    const derived = { ...variants[variant], ...overrides };
    if (overrides.style && overrides.style !== 'text') delete derived.underline;
    if (overrides.style && overrides.style !== 'solid') delete derived.label;
    if (derived.style === 'solid' && overrides.style === 'solid') delete derived.underline;
    const name = `use-${context}`;
    variants[name] = derived;
    use[context] = name;
  }

  return { variants, type: { ...type }, hover, use };
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
    const { style, colour, underline, label: labelColour, contrast } = declaration.variants[variant];
    const label = `${variant} button`;
    if (style === 'solid' && contrast === 'label') {
      // the colour stays as declared on every surface (a pale grey button on white): only its label has to read
      const text = labelColour !== undefined && r(labelColour, colour) >= THRESHOLD.label ? labelColour : labelOn(colour);
      return { style, bg: colour, text, border: colour };
    }
    if (style === 'solid') {
      const fill = pass(colour, (c) => r(c, surface) >= THRESHOLD.surface && r(labelOn(c), c) >= THRESHOLD.label, `the ${label} fill`);
      if (fill === null) {
        // nothing of this hue works on this surface: keep the declared fill, label it by contrast and draw the edge in the label colour so the shape shows
        const lab = labelOn(colour);
        notes.push(`${where}: no ${colour} step reaches ${THRESHOLD.surface}:1 against the surface and a readable label; the ${label} keeps its fill and gets a ${lab} edge.`);
        return { style, bg: colour, text: lab, border: lab };
      }
      // a declared label colour (tcs's grey Apply button) is kept where it reads on the fill, else the palette's white or black
      const text = labelColour !== undefined && r(labelColour, fill) >= THRESHOLD.label ? labelColour : labelOn(fill);
      return { style, bg: fill, text, border: fill };
    }
    const edgeAndLabel = pass(colour, (c) => r(c, surface) >= THRESHOLD.label, `the ${label} ${style === 'outline' ? 'label and edge' : 'label'}`) ?? bestNeutral(surface);
    if (style === 'outline') return { style, bg: 'transparent', text: edgeAndLabel, border: edgeAndLabel };
    if (underline === 'none') return { style, bg: 'transparent', text: edgeAndLabel, border: 'transparent' };
    // the underline is decoration under a label that is itself 4.5:1, so it only has to show (1.5:1), not carry 3:1
    const line = pass(underline ?? colour, (c) => r(c, surface) >= THRESHOLD.underline, `the ${label} underline`) ?? edgeAndLabel;
    return { style, bg: 'transparent', text: edgeAndLabel, border: line };
  }

  const hoverOf = (variant, base) => {
    const { style, colour, hover: ownHover } = declaration.variants[variant];
    const mode = ownHover ?? declaration.hover;
    const lighter = (ref) => nearby(ref).find((c) => resolver.value(c) && r(c, ref) >= 1.12 && luminance(c) > luminance(ref));
    const darker = (ref) => nearby(ref).find((c) => resolver.value(c) && r(c, ref) >= 1.12 && luminance(c) < luminance(ref));
    const luminance = (ref) => { const v = resolver.value(ref); return v === null ? 1 : wcagLuminance(v); };

    if (mode === 'none') return { hoverBg: base.bg, hoverText: base.text, hoverBorder: base.border };

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
  for (const variant of Object.keys(declaration.variants)) {
    const base = one(variant);
    result[variant] = { ...base, ...hoverOf(variant, base) };
  }
  result.notes = notes;
  return result;
}

/** resources/generated/buttons.php: the declaration for the PHP side (the core Button block's styles, the Editor's restricted controls, the drift row). Written only when the site declares `buttons`. */
export function buildButtonsPhp(declaration) {
  const quote = (value) => (typeof value === 'number' ? String(value) : `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`);
  const row = (variant) => `    '${variant}' => [${Object.entries(declaration.variants[variant]).map(([key, value]) => `'${key}' => ${quote(value)}`).join(', ')}],`;
  const type = Object.entries(declaration.type).map(([key, value]) => `'${key}' => ${quote(value)}`).join(', ');
  const use = Object.entries(declaration.use).map(([key, value]) => `'${key}' => ${quote(value)}`).join(', ');

  return [
    '<?php', '', '/**', ' * Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json (buttons).', ' * Do not edit directly. Read by the kit (app/buttons.php) and the drift check. Present only when the site declares `buttons`.', ' */', '',
    'return [', ...Object.keys(declaration.variants).map(row), `    'type' => [${type}],`, `    'use' => [${use}],`, `    'hover' => '${declaration.hover}',`, '];', '',
  ].join('\n');
}
