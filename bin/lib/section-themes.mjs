/**
 * Section themes: one SOLID and one LIGHT theme per palette colour, each a role matrix of CSS variables
 * (--section-bg, -heading, -text, -muted, -link, -button-bg, -button-text, -button-alt-bg, -button-alt-text, -icon, -border), plus a
 * dark-mode matrix applied under the site's `.dark` class. Pure functions (no file access, no process.exit): the generator
 * (bin/generate-theme-tokens.mjs) calls them and writes the files; tests/js/section-themes.test.mjs calls them directly.
 *
 * Where the values come from, in order (the first wins, per role):
 *   1. what the palette declares: `styles.<role>` for the solid theme, `styles.light.<role>` for the light theme, `styles.dark.<role>`
 *      for the solid theme in dark mode and `styles.light.dark.<role>` for the light theme in dark mode. The older keys (`content`,
 *      `highlight`, `buttonPrimary*`, `buttonSecondary*`, `card`) belong to the older .theme-{name} classes and are NOT read here, so
 *      adopting the new themes never turns an existing palette red;
 *   2. otherwise the best-contrast palette colour, worked out here (autofill).
 * A value is a palette reference: `sage-200` (a step), `sage-exact` or `sage` (the colour's exact hex), `#ffffff`, `transparent`, or for
 * `muted` an object { "color": <reference>, "opacity": 70 }. Bare `white` and `black` are the palette's own white and black exacts.
 */
import { wcagContrast, toRgb, shade, steps } from './color-math.mjs';
import { resolveButtonsFor, BUTTON_CONTEXTS, TYPE_CASES, TYPE_SIZES, TYPE_FONTS } from './buttons.mjs';

// Optional roles: written to the CSS only where a theme declares them, so a palette without them generates exactly what it did before.
//   buttonRing       the filled button's inset ring (falls back to buttonBorder, as before); `transparent` for no ring
//   buttonHoverText  the filled button's label on hover and keyboard focus (without it the label keeps its colour)
//   buttonStyle      "filled" (default) or "underline": a text button, no fill or ring or radius, an underline of --pkde-button-underline-width in the buttonBorder colour
export const OPTIONAL_ROLES = ['buttonRing', 'buttonHoverText', 'buttonAltBorder'];
export const BUTTON_STYLES = ['filled', 'underline'];
export const ROLE_KEYS = ['heading', 'text', 'muted', 'link', 'buttonBg', 'buttonText', 'buttonBorder', 'buttonAltBg', 'buttonAltText', 'icon', 'border', 'eyebrow', 'accent'];
// The roles of a card sitting in the section (a testimonial, a product, a post, a feature item). Declared under `styles.card` (an object; the older string `card`
// is a pointer for the older .pkde-card-* classes and is not read here) as { background, heading, text, muted, border, buttonBg, buttonText, buttonBorder, icon }.
export const CARD_KEYS = ['cardBg', 'cardHeading', 'cardText', 'cardMuted', 'cardBorder', 'cardButtonBg', 'cardButtonText', 'cardButtonBorder', 'cardIcon', 'cardEyebrow', 'cardAccent'];
const CARD_DECLARED = { background: 'cardBg', heading: 'cardHeading', text: 'cardText', muted: 'cardMuted', border: 'cardBorder', buttonBg: 'cardButtonBg', buttonText: 'cardButtonText', buttonBorder: 'cardButtonBorder', buttonHoverText: 'cardButtonHoverText', icon: 'cardIcon', eyebrow: 'cardEyebrow', accent: 'cardAccent' };
const CSS_NAME = { bg: 'bg', heading: 'heading', text: 'text', muted: 'muted', link: 'link', buttonBg: 'button-bg', buttonText: 'button-text', buttonBorder: 'button-border', buttonRing: 'button-ring', buttonHoverText: 'button-hover-text', buttonAltBorder: 'button-alt-border', cardButtonHoverText: 'card-button-hover-text', buttonAltBg: 'button-alt-bg', buttonAltText: 'button-alt-text', icon: 'icon', border: 'border', cardBg: 'card-bg', cardHeading: 'card-heading', cardText: 'card-text', cardMuted: 'card-muted', cardBorder: 'card-border', cardButtonBg: 'card-button-bg', cardButtonText: 'card-button-text', cardButtonBorder: 'card-button-border', cardIcon: 'card-icon', eyebrow: 'eyebrow', accent: 'accent', cardEyebrow: 'card-eyebrow', cardAccent: 'card-accent' };
export const THRESHOLD = { text: 4.5, heading: 3, muted: 4.5, link: 4.5, buttonText: 4.5, buttonAltText: 4.5, icon: 3, eyebrow: 4.5, accent: 3, buttonSurface: 3, linkText: 3 };
// An eyebrow or accent is drawn in the brand's highlight, so it has to stand apart from the section's own text colour (not the same dark or light).
const HIGHLIGHT_APART = 1.5;
// ...and it keeps the hue: the near-white (50, 100) and near-black (900, 950) ends of a ramp are not tried, only the exact colour and the steps 200 to 800 around it.
// A colour is WEAK when its best text colour barely clears AA (under this ratio) and no palette colour can serve as a heading or link on it:
// the matrix then has one readable text colour and nothing else to draw with. a mid-green brand colour (4.66), the template's accent and stone, a warm stone.
export const WEAK_BELOW = 5.2;
const OPACITY_STEPS = [50, 55, 60, 65, 70, 75, 80, 85, 90, 95];

export function titleCase(slug) {
  return slug.split('-').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

/** The human label of a colour: its `label`, else its name title-cased. The name (the slug) never changes. */
export function labelOf(color) {
  return typeof color.label === 'string' && color.label.trim() ? color.label.trim() : titleCase(color.name);
}

export function createResolver(colors) {
  const byName = new Map(colors.map((color) => [color.name, color]));

  function parse(ref) {
    if (typeof ref !== 'string') return null;
    if (ref === 'transparent') return { kind: 'transparent' };
    if (/^#[0-9a-f]{6}$/i.test(ref)) return { kind: 'hex', hex: ref.toLowerCase() };
    if (byName.has(ref)) return { kind: 'exact', name: ref };
    const cut = ref.lastIndexOf('-');
    if (cut < 1) return null;
    const owner = byName.get(ref.slice(0, cut));
    const tail = ref.slice(cut + 1);
    if (!owner) return null;
    if (tail === 'exact') return { kind: 'exact', name: owner.name };
    const step = Number(tail);
    return steps.includes(step) ? { kind: 'token', name: owner.name, step } : null;
  }

  /** A colour culori can read, or null for transparent. */
  function value(ref) {
    const parsed = parse(ref);
    if (!parsed || parsed.kind === 'transparent') return null;
    if (parsed.kind === 'hex') return parsed.hex;
    const owner = byName.get(parsed.name);
    return parsed.kind === 'exact' ? owner.hex : shade(owner, parsed.step);
  }

  /** The CSS for a reference (a custom property of the generated tokens, so a hand-tuned token still flows through). */
  function css(ref) {
    const parsed = parse(ref);
    if (!parsed) throw new Error(`Unknown colour reference ${JSON.stringify(ref)}`);
    if (parsed.kind === 'transparent') return 'transparent';
    if (parsed.kind === 'hex') return parsed.hex;
    return parsed.kind === 'exact' ? `var(--color-${parsed.name}-exact)` : `var(--color-${parsed.name}-${parsed.step})`;
  }

  return { parse, value, css, byName };
}

/** A matrix value as { color: <reference>, opacity: 1..100 }; a plain reference is opacity 100. */
function normalise(spec) {
  if (spec && typeof spec === 'object') return { color: spec.color, opacity: Math.max(1, Math.min(100, Number(spec.opacity ?? 100))) };
  return { color: spec, opacity: 100 };
}

/**
 * @param {Array} colors the palette
 * @param {{ buttons?: object|null }} [options] `buttons`: the resolved `buttons` declaration (bin/lib/buttons.mjs); with it the button roles of every section and card are OUTPUTS of one declaration
 *   (a role a colour declares itself still wins), without it nothing changes.
 */
export function createBuilder(colors, { buttons: buttonsDecl = null } = {}) {
  const resolver = createResolver(colors);
  const whiteColor = colors.find((color) => color.role === 'white');
  const blackColor = colors.find((color) => color.role === 'black');
  const neutrals = [whiteColor, blackColor].filter(Boolean).map((color) => color.name);
  // The colours a heading, link, icon or button can be drawn in: the palette's exact colours, accent first, then brand, then the rest.
  const brandish = colors
    .filter((color) => color.role !== 'black' && color.role !== 'white')
    .sort((a, b) => (a.role === 'accent' ? -1 : b.role === 'accent' ? 1 : 0) || (a.role === 'brand' ? -1 : b.role === 'brand' ? 1 : 0));

  function ratio(spec, bgRef) {
    const { color, opacity } = normalise(spec);
    const fg = resolver.value(color);
    const bg = resolver.value(bgRef);
    if (fg === null || bg === null) return null; // transparent: nothing to measure here
    if (opacity >= 100) return wcagContrast(fg, bg);
    const f = toRgb(fg);
    const b = toRgb(bg);
    const a = opacity / 100;
    return wcagContrast({ mode: 'rgb', r: f.r * a + b.r * (1 - a), g: f.g * a + b.g * (1 - a), b: f.b * a + b.b * (1 - a) }, bg);
  }

  function bestNeutral(bgRef) {
    return [...neutrals].sort((a, b) => ratio(b, bgRef) - ratio(a, bgRef))[0];
  }

  function lightnessOf(bgRef) {
    return wcagContrast(resolver.value(bgRef), '#ffffff') < 3 ? 'light' : 'dark';
  }

  /** The step of `owner` whose contrast against the background is nearest 1.6:1 (a decorative rule, no minimum). */
  function borderFor(ownerName, bgRef) {
    let best = null;
    for (const step of steps) {
      const r = ratio(`${ownerName}-${step}`, bgRef);
      if (r === null || r < 1.05) continue;
      if (best === null || Math.abs(r - 1.6) < Math.abs(best.r - 1.6)) best = { step, r };
    }
    return best ? `${ownerName}-${best.step}` : bestNeutral(bgRef);
  }

  /**
   * The brand highlight for a small decoration on a background (an eyebrow at 4.5:1, a rule or icon accent at 3:1): the first palette colour (accent, then brand, then the
   * rest; a SOLID theme leaves its own colour out) whose exact value passes, else the nearest step of that same hue that does (darker on a light background, lighter on a
   * dark one); a colour that cannot be told from the section's text is skipped. null when no hue can do it.
   */
  function highlightFor(bgRef, ownerName, { solid, min, textRef }) {
    for (const color of brandish) {
      if (solid && color.name === ownerName) continue;
      const exact = resolver.value(color.name);
      const nearest = steps.filter((step) => step > 100 && step < 900)
        .map((step) => ({ ref: `${color.name}-${step}`, gap: Math.abs(Math.log(wcagContrast(resolver.value(`${color.name}-${step}`), exact))) }))
        .sort((a, b) => a.gap - b.gap);
      for (const ref of [color.name, ...nearest.map((n) => n.ref)]) {
        if ((ratio(ref, bgRef) ?? 0) >= min && (ratio(ref, textRef) ?? 21) >= HIGHLIGHT_APART) return ref;
      }
    }
    return null;
  }

  /**
   * The matrix for a background, filled in by contrast alone. `ownerName` is the colour the background belongs to; a SOLID theme leaves
   * that colour out of the heading/link candidates (it would vanish on itself), a light theme keeps it (and its deep shades).
   */
  function autofill(bgRef, ownerName, { solid, buttonMin = 3, textRef = null }) {
    const text = textRef ?? bestNeutral(bgRef);
    const exacts = brandish.filter((color) => !(solid && color.name === ownerName)).map((color) => color.name);
    const ownerShades = solid ? [] : [`${ownerName}-800`, `${ownerName}-900`];
    const tints = brandish.flatMap((color) => (lightnessOf(bgRef) === 'dark' ? [`${color.name}-50`] : [`${color.name}-950`]));
    const candidates = [...exacts, ...ownerShades, ...tints];
    const pick = (min) => candidates.find((ref) => ratio(ref, bgRef) >= min) ?? null;

    const weak = ratio(text, bgRef) < WEAK_BELOW && !exacts.some((ref) => ratio(ref, bgRef) >= 4.5);
    const border = borderFor(ownerName, bgRef);

    if (weak) {
      // Only one text colour is readable on this colour, so every text role uses it (no brand-coloured heading or link, no muted tone).
      return { weak, matrix: { heading: text, text, muted: text, link: text, buttonBg: text, buttonText: bgRef, buttonBorder: text, buttonAltBg: 'transparent', buttonAltText: text, icon: text, border, eyebrow: text, accent: text } };
    }

    let muted = text;
    for (const opacity of OPACITY_STEPS) {
      if (ratio({ color: text, opacity }, bgRef) >= 4.5) { muted = { color: text, opacity }; break; }
    }

    const button = brandish
      .map((color) => color.name)
      .find((ref) => ratio(ref, bgRef) >= buttonMin && Math.max(...neutrals.map((n) => ratio(n, ref))) >= 4.5 && ref !== ownerName);
    const buttonBg = button ?? text;
    const buttonText = button ? bestNeutral(button) : bgRef;
    // The button's edge: invisible (its own colour) unless the button barely shows against the section (under 3:1), then the label colour, so its shape is visible.
    const buttonBorder = (ratio(buttonBg, bgRef) ?? 21) < THRESHOLD.buttonSurface ? buttonText : buttonBg;

    // the decorative colour (small rules, dividers and the kit's icons): a brand highlight at 3:1; the icon role defaults to it, a declared `icon` still wins
    const accent = highlightFor(bgRef, ownerName, { solid, min: THRESHOLD.accent, textRef: text }) ?? pick(3) ?? text;

    return {
      weak,
      matrix: {
        heading: pick(4.5) ?? pick(3) ?? text,
        text,
        muted,
        // its own colour where one works: 4.5:1 on the background and 3:1 against the text, so it is told apart by colour; otherwise the text colour (underlined by the CSS)
        link: candidates.find((ref) => ratio(ref, bgRef) >= 4.5 && (ratio(ref, text) ?? 21) >= THRESHOLD.linkText) ?? text,
        buttonBg,
        buttonText,
        buttonBorder,
        // a true outline: no fill, the text colour for the label and the edge
        buttonAltBg: 'transparent',
        buttonAltText: text,
        icon: accent,
        border,
        eyebrow: highlightFor(bgRef, ownerName, { solid, min: THRESHOLD.eyebrow, textRef: text }) ?? candidates.find((ref) => ratio(ref, bgRef) >= 4.5 && (ratio(ref, text) ?? 21) >= THRESHOLD.linkText) ?? text,
        accent,
      },
    };
  }

  /**
   * The card roles for a section: the card is white on a solid or tinted section (or the palette's white), the brand colour's lightest tint on a white section
   * (a white card would vanish; a pale tint such as blush-50 still gets a white card), a deep shade in dark mode; its text, icon and border follow from that background, and its button is the first brand colour that
   * reaches 4.5:1 on it (so a white card gets a sage or a pink button), never the card's own hue.
   */
  function cardFor(bgRef, ownerName, scope, declaredBg = null) {
    const white = whiteColor?.name ?? 'white';
    const brand = colors.find((color) => color.role === 'brand') ?? brandish[0];
    let cardBg;
    let cardOwner;

    if (scope === 'dark') {
      cardOwner = ownerName;
      cardBg = [`${ownerName}-800`, `${ownerName}-950`, `${ownerName}-700`].find((ref) => { const r = ratio(ref, bgRef); return r !== null && r >= 1.08 && r <= 2.2; }) ?? `${ownerName}-800`;
    } else if ((ratio(white, bgRef) ?? 21) < 1.03) {
      cardOwner = brand.name;
      cardBg = `${brand.name}-50`;
    } else {
      cardOwner = white;
      cardBg = white;
    }

    if (declaredBg) {
      // the palette names the card's background: its text, icon, border and button are worked out for THAT background
      const parsed = resolver.parse(normalise(declaredBg).color);
      cardBg = declaredBg;
      cardOwner = parsed?.name ?? cardOwner;
    }

    const auto = autofill(cardBg, cardOwner, { solid: false, buttonMin: 4.5 });
    const m = auto.matrix;

    return { cardBg, cardHeading: m.heading, cardText: m.text, cardMuted: m.muted, cardBorder: m.border, cardButtonBg: m.buttonBg, cardButtonText: m.buttonText, cardButtonBorder: m.buttonBorder, cardIcon: m.icon, cardEyebrow: m.eyebrow, cardAccent: m.accent };
  }

  /** The declared card roles of a theme ({ cardBg?, cardHeading?, ... }), references checked. */
  function declaredCards(color, kind) {
    const styles = color.styles ?? {};
    const source = kind === 'solid' ? styles : kind === 'light' ? styles.light : kind === 'dark' ? styles.dark : styles.light?.dark;
    const card = source?.card;
    const result = {};
    if (!card || typeof card !== 'object') return result;

    for (const [key, role] of Object.entries(CARD_DECLARED)) {
      if (card[key] === undefined) continue;
      if (!resolver.parse(normalise(card[key]).color)) throw new Error(`${color.name}: styles${kind === 'solid' ? '' : `.${kind === 'light' ? 'light' : kind === 'dark' ? 'dark' : 'light.dark'}`}.card.${key} is not a known colour reference (${JSON.stringify(card[key])}).`);
      result[role] = card[key];
    }

    return result;
  }

  /** The declared values of a theme: { background?, <role>? } with references checked. */
  function declared(color, kind) {
    const styles = color.styles ?? {};
    const source = kind === 'solid' ? styles : kind === 'light' ? styles.light : kind === 'dark' ? styles.dark : styles.light?.dark;
    const result = {};
    if (!source || typeof source !== 'object') return result;
    for (const role of ROLE_KEYS) {
      const spec = source[role];
      if (spec === undefined) continue;
      const parts = normalise(spec);
      if (!resolver.parse(parts.color)) throw new Error(`${color.name}: styles${kind === 'solid' ? '' : `.${kind === 'light' ? 'light' : kind === 'dark' ? 'dark' : 'light.dark'}`}.${role} is not a known colour reference (${JSON.stringify(spec)}); use a palette step such as "${color.name}-800", "${color.name}-exact", "#ffffff" or "transparent".`);
      result[role] = spec;
    }
    for (const role of OPTIONAL_ROLES) {
      if (source[role] === undefined) continue;
      if (!resolver.parse(normalise(source[role]).color)) throw new Error(`${color.name}: styles${kind === 'solid' ? '' : `.${kind === 'light' ? 'light' : kind === 'dark' ? 'dark' : 'light.dark'}`}.${role} is not a known colour reference (${JSON.stringify(source[role])}).`);
      result[role] = source[role];
    }
    if (source.buttonStyle !== undefined) {
      if (!BUTTON_STYLES.includes(source.buttonStyle)) throw new Error(`${color.name}: styles${kind === 'solid' ? '' : `.${kind === 'light' ? 'light' : kind === 'dark' ? 'dark' : 'light.dark'}`}.buttonStyle must be one of ${BUTTON_STYLES.join(', ')}; got ${JSON.stringify(source.buttonStyle)}.`);
      result.buttonStyle = source.buttonStyle;
    }
    if (kind !== 'solid' && typeof source.background === 'string') {
      if (!resolver.parse(source.background)) throw new Error(`${color.name}: styles.${kind === 'light' ? 'light' : 'dark'}.background is not a known colour reference (${JSON.stringify(source.background)}).`);
      result.background = source.background;
    }
    if (kind === 'solid' && typeof styles.sectionBackground === 'string' && resolver.parse(styles.sectionBackground)) result.background = styles.sectionBackground;
    return result;
  }

  /** The three variants on a surface and the section and card button roles they imply: the roles are outputs of the one declaration. */
  function buttonOutputs(bgRef, cardBgRef, where) {
    const helpers = { resolver, ratio, bestNeutral };
    const btn = resolveButtonsFor(bgRef, buttonsDecl, helpers, where);
    const cardBtn = resolveButtonsFor(cardBgRef ?? bgRef, buttonsDecl, helpers, `${where}, card`);
    const p = btn.primary;
    const roles = {
      buttonBg: p.bg, buttonText: p.text, buttonBorder: p.border, buttonHoverText: p.hoverText,
      buttonAltBg: btn.secondary.bg, buttonAltText: btn.secondary.text, buttonAltBorder: btn.secondary.border,
      cardButtonBg: cardBtn.primary.bg, cardButtonText: cardBtn.primary.text, cardButtonBorder: cardBtn.primary.border, cardButtonHoverText: cardBtn.primary.hoverText,
    };
    return { roles, btn, cardBtn, notes: [...btn.notes, ...cardBtn.notes] };
  }

  /** The three variants on the page itself (a white page, cards on it): the :root values. */
  function pageButtons() {
    if (!buttonsDecl) return null;
    const page = whiteColor?.name ?? 'white';
    const result = resolveButtonsFor(page, buttonsDecl, { resolver, ratio, bestNeutral }, 'the page');
    return result;
  }

  function matrixFor(color, kind, bgRef, { solid }) {
    const auto = autofill(bgRef, color.name, { solid });
    const own = declared(color, kind);
    const { background: _ignored, ...roles } = own;
    const declaredCard = declaredCards(color, kind);
    const cards = { ...cardFor(bgRef, color.name, kind === 'dark' || kind === 'lightDark' ? 'dark' : 'light', declaredCard.cardBg ?? null), ...declaredCard };
    // a declared text colour moves the link's own colour (it has to stand apart from THAT text)
    if (roles.text !== undefined) {
      const moved = autofill(bgRef, color.name, { solid, textRef: roles.text }).matrix;
      for (const role of ['link', 'eyebrow', 'accent']) if (roles[role] === undefined) auto.matrix[role] = moved[role];
    }
    const out = buttonsDecl ? buttonOutputs(bgRef, cards.cardBg, `${labelOf(color)} (${kind})`) : { roles: {} };
    const merged = { weak: auto.weak, bg: bgRef, ...auto.matrix, ...cards, ...out.roles, ...declaredCard, ...roles, declared: Object.keys(roles), ...(buttonsDecl ? { btn: out.btn, cardBtn: out.cardBtn, btnNotes: out.notes } : {}) };
    // links that colour cannot tell from the text are underlined (the generated rule at the end of the section CSS)
    merged.underline = (ratio(merged.link, merged.text) ?? 1) < THRESHOLD.linkText;
    // the edges follow what was finally chosen: invisible unless the button barely shows, then the label colour (a declared edge wins)
    if (!buttonsDecl && roles.buttonBorder === undefined) merged.buttonBorder = (ratio(merged.buttonBg, bgRef) ?? 21) < THRESHOLD.buttonSurface ? merged.buttonText : merged.buttonBg;
    if (!buttonsDecl && declaredCard.cardButtonBorder === undefined) merged.cardButtonBorder = (ratio(merged.cardButtonBg, merged.cardBg) ?? 21) < THRESHOLD.buttonSurface ? merged.cardButtonText : merged.cardButtonBg;
    return merged;
  }

  /** Every theme of the palette: solid and light, each with its light-mode and dark-mode matrix. */
  function themes() {
    const list = [];
    for (const color of colors) {
      const label = labelOf(color);
      const solidBg = declared(color, 'solid').background ?? color.name;
      list.push({
        id: `brand-${color.name}`,
        label: `Brand ${label}`,
        colour: color.name,
        mode: 'solid',
        matrix: matrixFor(color, 'solid', solidBg, { solid: true }),
        dark: matrixFor(color, 'dark', declared(color, 'dark').background ?? solidBg, { solid: true }),
      });
      if (color.role === 'white') continue; // a light version of white is just white
      const lightBg = declared(color, 'light').background ?? `${color.name}-100`;
      const darkBg = declared(color, 'lightDark').background ?? `${color.name}-900`;
      list.push({
        id: `brand-${color.name}-light`,
        label: `Brand ${label} (light)`,
        colour: color.name,
        mode: 'light',
        matrix: matrixFor(color, 'light', lightBg, { solid: false }),
        dark: matrixFor(color, 'lightDark', darkBg, { solid: false }),
      });
    }
    return list;
  }

  const show = (spec) => {
    const { color, opacity } = normalise(spec);
    return opacity >= 100 ? color : `${color} at ${opacity}%`;
  };

  /** The contrast problems of one matrix: fails (below the threshold) and warns (the button surface, weak colours). */
  function check(theme, matrix, scope) {
    const fails = [];
    const warns = [];
    const where = `${theme.id}${scope === 'dark' ? ' (dark)' : ''}`;
    const need = (role, min, against = matrix.bg, label = role) => {
      const r = ratio(matrix[role], against);
      if (r !== null && r + 1e-9 < min) fails.push(`${where}: ${label} ${show(matrix[role])} measures ${r.toFixed(2)}:1 on ${against === matrix.bg ? 'the background' : against} (${against === matrix.bg ? matrix.bg : against}); it needs ${min}:1.`);
    };
    need('text', THRESHOLD.text);
    need('heading', THRESHOLD.heading);
    need('muted', THRESHOLD.muted);
    need('link', THRESHOLD.link);
    const linkText = ratio(matrix.link, matrix.text);
    if (linkText !== null && linkText > 1.05 && linkText < THRESHOLD.linkText) warns.push(`${where}: the link ${show(matrix.link)} measures ${linkText.toFixed(2)}:1 against the text ${show(matrix.text)}, too close to tell apart by colour; links are underlined there (give the link another colour, or accept the underline).`);
    need('icon', THRESHOLD.icon);
    need('eyebrow', THRESHOLD.eyebrow);
    need('accent', THRESHOLD.accent);
    // the button label on the button, the outline button's label on the section (or on its own fill)
    const buttonLabel = ratio(matrix.buttonText, matrix.buttonBg);
    if (buttonLabel !== null && buttonLabel + 1e-9 < THRESHOLD.buttonText) fails.push(`${where}: the button label ${show(matrix.buttonText)} measures ${buttonLabel.toFixed(2)}:1 on the button ${show(matrix.buttonBg)}; it needs 4.5:1.`);
    if (matrix.buttonHoverText !== undefined) {
      const hoverLabel = ratio(matrix.buttonHoverText, matrix.buttonBg === 'transparent' ? matrix.bg : matrix.buttonBg);
      if (hoverLabel !== null && hoverLabel + 1e-9 < THRESHOLD.buttonText) fails.push(`${where}: the button label on hover ${show(matrix.buttonHoverText)} measures ${hoverLabel.toFixed(2)}:1 on the button; it needs 4.5:1.`);
    }
    const altSurface = matrix.buttonAltBg === 'transparent' ? matrix.bg : matrix.buttonAltBg;
    const altLabel = ratio(matrix.buttonAltText, altSurface);
    if (altLabel !== null && altLabel + 1e-9 < THRESHOLD.buttonAltText) fails.push(`${where}: the outline button label ${show(matrix.buttonAltText)} measures ${altLabel.toFixed(2)}:1 on ${altSurface}; it needs 4.5:1.`);
    const surface = ratio(matrix.buttonBg, matrix.bg);
    if (surface !== null && surface + 1e-9 < THRESHOLD.buttonSurface && (ratio(matrix.buttonBorder, matrix.bg) ?? 0) < THRESHOLD.buttonSurface) warns.push(`${where}: the button (${show(matrix.buttonBg)}) measures ${surface.toFixed(2)}:1 against the section background; its label is readable but the button's shape is hard to see (3:1 is the guide).`);
    // the card in the section
    const needCard = (role, min, label) => {
      const r = ratio(matrix[role], matrix.cardBg);
      if (r !== null && r + 1e-9 < min) fails.push(`${where}: card ${label} ${show(matrix[role])} measures ${r.toFixed(2)}:1 on the card (${matrix.cardBg}); it needs ${min}:1.`);
    };
    needCard('cardText', THRESHOLD.text, 'text');
    needCard('cardHeading', THRESHOLD.heading, 'heading');
    needCard('cardMuted', THRESHOLD.muted, 'muted text');
    needCard('cardIcon', THRESHOLD.icon, 'icon');
    needCard('cardEyebrow', THRESHOLD.eyebrow, 'eyebrow');
    needCard('cardAccent', THRESHOLD.accent, 'accent');
    const cardLabel = ratio(matrix.cardButtonText, matrix.cardButtonBg);
    if (cardLabel !== null && cardLabel + 1e-9 < THRESHOLD.buttonText) fails.push(`${where}: the card button label ${show(matrix.cardButtonText)} measures ${cardLabel.toFixed(2)}:1 on the card button ${show(matrix.cardButtonBg)}; it needs 4.5:1.`);
    const cardSurface = ratio(matrix.cardButtonBg, matrix.cardBg);
    if (cardSurface !== null && cardSurface + 1e-9 < THRESHOLD.buttonSurface && ratio(matrix.cardButtonBorder, matrix.cardBg) < THRESHOLD.buttonSurface) warns.push(`${where}: the card button (${show(matrix.cardButtonBg)}) measures ${cardSurface.toFixed(2)}:1 against the card; its shape is hard to see.`);
    for (const note of matrix.btnNotes ?? []) warns.push(`${note}`);
    if (matrix.weak && scope !== 'dark') warns.push(`${where}: a weak colour. Only ${show(matrix.text)} is readable on it (${ratio(matrix.text, matrix.bg).toFixed(2)}:1), so every text role uses it: no brand-coloured heading or link and no muted tone, and links look like body text.`);
    return { fails, warns };
  }

  function checkAll(list, { accept = [] } = {}) {
    const fails = [];
    const warns = [];
    for (const theme of list) {
      for (const [scope, matrix] of [['light', theme.matrix], ['dark', theme.dark]]) {
        const result = check(theme, matrix, scope);
        const accepted = accept.includes(theme.colour);
        (accepted ? warns : fails).push(...result.fails.map((line) => (accepted ? `${line} (accepted: acceptLowContrast)` : line)));
        warns.push(...result.warns);
      }
    }
    return { fails, warns: groupButtonNotes(warns) };
  }

  /** The button substitution notes are one per surface; group them by what changed ("the primary button fill accent fails contrast, accent-500 used: 12 surfaces"). */
  function groupButtonNotes(warns) {
    const groups = new Map();
    const rest = [];
    for (const line of warns) {
      const m = /^(.*?): (the [a-z]+ button [^:]*? fails contrast, \S+ used\.|no \S+ step reaches .*)$/.exec(line);
      if (!m) { rest.push(line); continue; }
      const key = m[2].replace(/\.$/, '');
      groups.set(key, [...(groups.get(key) ?? []), m[1]]);
    }
    const grouped = [...groups].map(([what, where]) => `Buttons: ${what}, on ${where.length} surface${where.length === 1 ? '' : 's'} (${[...new Set(where)].slice(0, 3).join('; ')}${where.length > 3 ? '; ...' : ''}).`);
    return [...rest, ...grouped];
  }

  /**
   * The button variables of a legacy `.theme-<name>` section (the hero, CTA, FAQ and loop blocks' `theme` attribute): resolved against that theme's own background, so a
   * hero on a dark theme gets readable buttons from the same declaration. Empty without `buttons`.
   */
  function legacyThemeButtons(list, aliases) {
    if (!buttonsDecl) return '';
    const out = [];
    list.forEach((color, index) => {
      const bgRef = typeof color.styles?.background === 'string' && resolver.parse(color.styles.background) ? color.styles.background : color.name;
      const btn = resolveButtonsFor(bgRef, buttonsDecl, { resolver, ratio, bestNeutral }, `${labelOf(color)} (older theme-${color.name} class)`);
      const selectors = [`.theme-${color.name}`, ...(aliases[index] ? [`.theme-${aliases[index]}`] : [])];
      out.push(`${selectors.join(',\n')} {`, ...buttonVariables(resolver, btn, '--btn-'), '}', '');
    });
    return out.join('\n');
  }

  return { resolver, autofill, themes, check, checkAll, ratio, pageButtons, legacyThemeButtons, buttons: buttonsDecl };
}

const cssValue = (resolver, spec) => {
  const { color, opacity } = normalise(spec);
  const base = resolver.css(color);
  return opacity >= 100 || base === 'transparent' ? base : `color-mix(in srgb, ${base} ${opacity}%, transparent)`;
};

const HOVER_KEYS = [['hoverBg', 'hover-bg'], ['hoverText', 'hover-text'], ['hoverBorder', 'hover-border']];

/** `--btn-<variant>-bg|text|border|hover-bg|hover-text|hover-border` for the three variants on one surface (prefix `--btn-` or `--btn-card-`). */
function buttonVariables(resolver, btn, prefix) {
  const lines = [];
  for (const variant of Object.keys(btn).filter((key) => key !== 'notes')) {
    const v = btn[variant];
    lines.push(`  ${prefix}${variant}-bg: ${resolver.css(v.bg)};`, `  ${prefix}${variant}-text: ${resolver.css(v.text)};`, `  ${prefix}${variant}-border: ${resolver.css(v.border)};`);
    for (const [key, name] of HOVER_KEYS) lines.push(`  ${prefix}${variant}-${name}: ${resolver.css(v[key])};`);
  }
  return lines;
}

// The classes every button of the site is drawn from. The kit's own buttons carry .pkde-btn (--primary, --secondary, --text), the core Button block its block styles (Solid is the
// default, Outline, Text), WooCommerce and Contact Form 7 their own markup. A button with an explicit class or block style has that variant; the others are in a POSITION (a
// context), and the site's `use` map says which variant each position draws (the default is primary, Reset is text). The lists are disjoint, so no rule depends on specificity.
const NOT_VARIANT = ':not(.pkde-btn-alt):not(.pkde-btn--secondary):not(.pkde-btn--text)';
const CARD_BOXES = 'article, li.product, .pkde-feature, .pkde-quote, .pkde-card, .pkde-product-card';
const HERO_BOXES = '.wp-block-sage-hero, .wp-block-sage-hero-slider, .wp-block-sage-cta, .pkde-cta-band, .pkde-section';
const FIXED_SELECTORS = {
  primary: ['.pkde-btn--primary'],
  secondary: ['.pkde-btn-alt', '.pkde-btn--secondary', '.wp-block-button.is-style-outline > .wp-block-button__link', '.pkde-product-section__more-button', '.pkde-recent__button'],
  text: ['.pkde-btn--text', '.wp-block-button.is-style-text > .wp-block-button__link'],
};
const CONTEXT_SELECTORS = {
  card: [`:where(${CARD_BOXES}) .pkde-btn${NOT_VARIANT}`, ':where(li.product, .products .product) :is(a.button, button.button, input.button)', '.add_to_cart_button'],
  hero: [`:where(${HERO_BOXES}) .pkde-btn${NOT_VARIANT}:not(:where(${CARD_BOXES}) *)`],
  product: ['.single_add_to_cart_button'],
  filters: ['.pkde-filter-apply'],
  reset: ['.pkde-filter-reset'],
  forms: ['.wpcf7-submit', '.comment-form input[type="submit"]', '.wp-block-search__button'],
  // core Button blocks in the content that have the default (Solid) style; an Outline or Text block keeps its own variant
  content: ['.wp-block-button:not(.is-style-outline):not(.is-style-text) > .wp-block-button__link'],
  // the classic basket's own buttons: Update basket and the coupon's Apply (they follow `cart` unless `basket` is set)
  basket: ['.woocommerce table.cart td.actions :is(button[name="update_cart"], button[name="apply_coupon"])', '.woocommerce-cart-form button[name="update_cart"]', '.woocommerce-cart-form button[name="apply_coupon"]', '.woocommerce form.checkout_coupon button[name="apply_coupon"]'],
  cart: ['a.checkout-button', 'button[name="woocommerce_checkout_place_order"]', '.wc-block-components-button:not(.is-link):not(.wc-block-components-panel__button)', '.woocommerce .wc-backward'],
  nav: ['li.nav-cta > a'],
  other: [`.pkde-btn${NOT_VARIANT}:not(:where(${CARD_BOXES}, ${HERO_BOXES}) *)`, '.woocommerce :is(a.button, button.button, input.button):not(.single_add_to_cart_button, .add_to_cart_button, .checkout-button, .wc-backward, [name="update_cart"], [name="apply_coupon"], [name="woocommerce_checkout_place_order"], .wpcf7-submit, .pkde-btn-alt, .pkde-btn--secondary, .pkde-btn--text):not(:where(li.product, .products .product) *)'],
};
// where a card is: the variables of the card's own surface replace the section's inside it
const CARD_CONTEXTS = ['article', '.woocommerce ul.products > .product', '.pkde-quotes--strip .pkde-quote', '.pkde-quotes--carousel .pkde-quote', '.pkde-features--cards .pkde-feature', '.pkde-card'];

const variantNames = (btn) => Object.keys(btn).filter((key) => key !== 'notes');

/** The static look of a variant: the border width, radius and padding its style implies, and the type it (or the site's `type`) sets. */
function variantLook(decl, name) {
  const v = decl.variants[name];
  const text = v.style === 'text';
  const radiusOf = { none: '0', sm: 'var(--pkde-radius-sm)', md: 'var(--pkde-radius-md)', lg: 'var(--pkde-radius-lg)', full: 'var(--pkde-radius-full)' };
  const look = {
    bw: text ? (v.underline === 'none' ? '0' : '0 0 var(--pkde-button-underline-width) 0') : (v.border === 'none' ? '0' : (v.border ?? 'var(--pkde-border-thick)')),
    radius: v.radius !== undefined ? (radiusOf[v.radius] ?? v.radius) : (text ? '0' : 'var(--pkde-radius-full)'),
    padding: v.padding === 'keep' ? null : (v.padding ?? (text ? '0 0 0.25rem' : '0.75rem 1.25rem')),
    type: [],
  };
  const pick = (key) => v[key] ?? decl.type[key];
  const font = pick('font');
  if (font) look.type.push(`  font-family: ${TYPE_FONTS[font] ?? (/^[A-Za-z0-9 _-]+$/.test(font) ? `"${font}"` : font)};`);
  if (pick('case')) look.type.push(`  text-transform: ${TYPE_CASES[pick('case')]};`);
  if (pick('weight')) look.type.push(`  font-weight: ${pick('weight')};`);
  if (pick('tracking')) look.type.push(`  letter-spacing: ${pick('tracking')};`);
  if (pick('size')) look.type.push(`  font-size: ${TYPE_SIZES[pick('size')] ?? pick('size')};`);
  if (v.height) look.type.push(`  min-height: ${v.height};`);
  if (v.lineHeight !== undefined) look.type.push(`  line-height: ${v.lineHeight};`);
  return look;
}

/**
 * The structure of each variant (surface independent) on :root, then the rules that draw every button from the variables. The selectors are repeated so they stand one level
 * above the older per-component button rules without !important.
 */
function globalButtonCss(decl, page, resolver) {
  const out = [];
  const names = Object.keys(decl.variants);
  const looks = Object.fromEntries(names.map((name) => [name, variantLook(decl, name)]));

  out.push('/**', ' * One global button system: `buttons` in config/brand-palette.json, resolved for every surface below. Every button of the site is drawn from these variables.', ' */', ':root {');
  for (const name of names) {
    out.push(`  --btn-${name}-border-width: ${looks[name].bw};`, `  --btn-${name}-radius: ${looks[name].radius};`);
    if (looks[name].padding !== null) out.push(`  --btn-${name}-padding: ${looks[name].padding};`);
  }
  out.push(...buttonVariables(resolver, page, '--btn-'), ...buttonVariables(resolver, page, '--btn-card-'), '}', '');

  // a card replaces the section's button variables with its own surface's
  const cardVars = variantNames(page).flatMap((variant) => ['bg', 'text', 'border', 'hover-bg', 'hover-text', 'hover-border'].map((k) => `  --btn-${variant}-${k}: var(--btn-card-${variant}-${k});`));
  out.push(`${CARD_CONTEXTS.map((c) => `:is(${c})`).join(',\n')} {`, ...cardVars, '}', '');

  for (const name of names) {
    const own = name.startsWith('use-') ? [] : (FIXED_SELECTORS[name] ?? []);
    // the basket's own buttons (Update basket, the coupon's Apply) follow the `cart` position unless `basket` says otherwise: a site that keeps its basket keeps them too
    const drawnBy = (context) => decl.use[context] ?? (context === 'basket' ? (decl.use.cart ?? BUTTON_CONTEXTS.basket) : BUTTON_CONTEXTS[context]);
    const mapped = Object.keys(CONTEXT_SELECTORS).filter((context) => drawnBy(context) === name).flatMap((context) => CONTEXT_SELECTORS[context]);
    const list = [...own, ...mapped];
    if (!list.length) continue;
    const look = looks[name];
    // the whole list three times: the selector stands at three times its strongest member (one level above the older per-component and card button rules, no id, no !important)
    const group = `:is(${list.join(', ')})`;
    const bump = (suffix = '') => `${group}${group}${group}${suffix}`;
    out.push(
      `/* ${name} (${decl.variants[name].style}) */`,
      // how the label sits in the box is a layout default (zero specificity), so a site's own row (align-items: flex-end under a text button) still wins
      `:where(${list.join(', ')}) {`, '  display: inline-flex;', '  align-items: center;', '  justify-content: center;', '}',
      `${bump()} {`,
      ...(decl.variants[name].style === 'text' ? [] : ['  display: inline-flex;', '  align-items: center;', '  justify-content: center;']), '  box-sizing: border-box;', '  font-family: inherit;', '  line-height: 1.25;',
      `  background-color: var(--btn-${name}-bg);`, `  color: var(--btn-${name}-text);`,
      '  border-style: solid;', `  border-width: var(--btn-${name}-border-width);`, `  border-color: var(--btn-${name}-border);`,
      `  border-radius: var(--btn-${name}-radius);`, ...(look.padding !== null ? [`  padding: var(--btn-${name}-padding);`] : []),
      '  box-shadow: none;', '  text-decoration: none;', '  cursor: pointer;', '  transition: background-color var(--pkde-motion-fast, 150ms) ease, color var(--pkde-motion-fast, 150ms) ease, border-color var(--pkde-motion-fast, 150ms) ease;',
      ...look.type, '}',
      `${bump(':is(:hover, :focus-visible)')} {`,
      `  background-color: var(--btn-${name}-hover-bg);`, `  color: var(--btn-${name}-hover-text);`, `  border-color: var(--btn-${name}-hover-border);`, '  opacity: 1;', '}',
      `${bump(':focus-visible')} {`, `  outline: 2px solid var(--btn-${name}-border);`, '  outline-offset: 2px;', '}',
      '',
    );
    if (list.includes('.pkde-filter-apply')) out.push('/* the filter form\'s Apply button is quieter while nothing is pending (it is never disabled) */', `${group}${group}${group}[data-pending="0"]:not(:hover):not(:focus-visible) {`, '  opacity: 0.6;', '}', '');
  }
  return out.join('\n');
}

function declarations(resolver, matrix) {
  const lines = [`  --section-bg: ${resolver.css(matrix.bg)};`];
  for (const role of [...ROLE_KEYS, ...CARD_KEYS]) lines.push(`  --section-${CSS_NAME[role]}: ${cssValue(resolver, matrix[role])};`);
  for (const role of [...OPTIONAL_ROLES, 'cardButtonHoverText']) if (matrix[role] !== undefined) lines.push(`  --section-${CSS_NAME[role]}: ${cssValue(resolver, matrix[role])};`);
  if (matrix.btn) lines.push(...buttonVariables(resolver, matrix.btn, '--btn-'), ...buttonVariables(resolver, matrix.cardBtn, '--btn-card-'));
  return lines;
}

// The older --pkde-theme-* tokens (what the kit's blocks and buttons read) and the card tokens follow the section's roles in the same way in every theme, so they are
// declared once for every themed element instead of in every theme.
const THEME_ALIASES = [
  '  --pkde-theme-background: var(--section-bg);',
  '  --pkde-theme-content: var(--section-text);',
  '  --pkde-theme-highlight: var(--section-link);',
  '  --pkde-theme-btn-bg: var(--section-button-bg);',
  '  --pkde-theme-btn-text: var(--section-button-text);',
  '  --pkde-theme-btn-border: var(--section-button-border);',
  '  --pkde-theme-alt-btn-bg: var(--section-button-alt-bg);',
  '  --pkde-theme-alt-btn-text: var(--section-button-alt-text);',
  '  --pkde-theme-alt-btn-border: var(--section-button-alt-border, var(--section-button-alt-text));',
  '  --pkde-theme-border: var(--section-border);',
];
const CARD_ALIASES = [
  '  --pkde-card-background: var(--section-card-bg);',
  '  --pkde-card-content: var(--section-card-text);',
  '  --pkde-card-accent: var(--section-card-heading);',
];

const SECTION = (ids, prefix) => `${prefix}:is(${ids.map((id) => `.is-style-${id}`).join(', ')})[class*="is-style-brand-"][class*="is-style-brand-"][class*="is-style-brand-"]`;
const NOT_OWN = ':not(:where(.pkde-section, .pkde-card, article, .product) *)';
const CARD_BUTTONS = ':is(article, .woocommerce ul.products > .product) :is(a.button, button.button, input.button, .pkde-btn):not(.pkde-btn-alt)';

/** The rules of the text button and the hover label, for the themes (in one mode: `matrix` or `dark`) that declare them. */
function buttonRules(themeList, key, prefix) {
  const out = [];
  const text = themeList.filter((theme) => theme[key].buttonStyle === 'underline').map((theme) => theme.id);
  const hover = themeList.filter((theme) => theme[key].buttonHoverText !== undefined).map((theme) => theme.id);
  const cardHover = themeList.filter((theme) => theme[key].cardButtonHoverText !== undefined).map((theme) => theme.id);
  if (text.length) {
    out.push(
      `/* Text buttons (buttonStyle "underline"): no fill, ring or radius; the underline is --pkde-button-underline-width in the button's edge colour. */`,
      `${SECTION(text, prefix)} :is(.wp-block-button:not(.is-style-outline) > .wp-block-button__link, .pkde-btn:not(.pkde-btn-alt))${NOT_OWN} {`,
      '  padding: 0 0 0.25rem;',
      '  border-radius: 0;',
      '  border-style: solid;',
      '  border-width: 0 0 var(--pkde-button-underline-width) 0;',
      '  border-color: transparent transparent var(--section-button-border) transparent;',
      '  box-shadow: none;',
      '}',
      `${SECTION(text, prefix)} ${CARD_BUTTONS} {`,
      '  padding: 0 0 0.25rem;',
      '  border-radius: 0;',
      '  border-style: solid;',
      '  border-width: 0 0 var(--pkde-button-underline-width) 0;',
      '  border-color: transparent transparent var(--section-card-button-border) transparent;',
      '  box-shadow: none;',
      '}',
      '',
    );
  }
  if (hover.length) {
    out.push(`${SECTION(hover, prefix)} :is(.wp-block-button:not(.is-style-outline) > .wp-block-button__link, .pkde-btn:not(.pkde-btn-alt)):is(:hover, :focus-visible)${NOT_OWN} {`, '  color: var(--section-button-hover-text);', '}', '');
  }
  if (cardHover.length) {
    out.push(`${SECTION(cardHover, prefix)} ${CARD_BUTTONS}:is(:hover, :focus-visible) {`, '  color: var(--section-card-button-hover-text);', '}', '');
  }
  return out;
}

/** The generated CSS of every section theme: the variables only (the kit's section.css paints the section from them). */
export function buildSectionRolesCss(colors, themeList, buttons = null) {
  const resolver = createResolver(colors);
  const out = [
    '/**',
    ' * Section themes: Brand <Label> and Brand <Label> (light) for every palette colour, as block styles (is-style-brand-*) of core/group and',
    ' * sage/section. Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json; do not edit. Set a role by hand in the colour\'s',
    ' * `styles` (see docs/section-themes.md), then regenerate.',
    ' */',
    '[class*="is-style-brand-"] {',
    ...THEME_ALIASES,
    ...CARD_ALIASES,
    '}',
    '',
  ];
  for (const theme of themeList) {
    out.push(`.is-style-${theme.id} {`, ...declarations(resolver, theme.matrix), '}', '');
  }
  const underlined = (key) => themeList.filter((theme) => theme[key].underline).map((theme) => `.is-style-${theme.id}`);
  const linkRule = (themes, prefix = '') => `${prefix}:is(${themes.join(', ')})[class*="is-style-brand-"][class*="is-style-brand-"] :is(p, li, dt, dd, td, th, blockquote, cite) a:where(:not(.wp-block-button__link, .pkde-btn, .pkde-btn-alt, .wp-element-button)):not(:where(.pkde-section, .pkde-card, article, .product, nav, .has-link-color, [class*="wp-elements-"]) *) {\n  text-decoration: underline;\n}`;
  const light = underlined('matrix');
  const dark = underlined('dark').filter((id) => !light.includes(id));
  out.push('/* Dark mode: under the site\'s .dark class (the same switch the site\'s own dark: variants use). Without that class nothing here applies. */');
  for (const theme of themeList) {
    out.push(`.dark .is-style-${theme.id} {`, ...declarations(resolver, theme.dark), '}', '');
  }
  // Text buttons (buttonStyle "underline") and the hover label (buttonHoverText): rules only for the themes that declare them, so nothing else changes.
  out.push(...buttonRules(themeList, 'matrix', ''), ...buttonRules(themeList, 'dark', '.dark '));
  // Links in running text that colour cannot tell from the text (the link role equals, or is within 3:1 of, the text role) are underlined; a link with its own colour is
  // left to the site's link style. Only the themes that need it are listed.
  if (light.length || dark.length) out.push('/* Links in running text that are not told apart by colour are underlined. Buttons, cards and nav links are left alone. */');
  if (light.length) out.push(linkRule(light), '');
  if (dark.length) out.push(linkRule(dark, '.dark '), '');
  // The one global button system: only for a palette that declares `buttons`.
  if (buttons?.declaration) out.push(globalButtonCss(buttons.declaration, buttons.page, resolver));
  return out.join('\n');
}

/**
 * The CSS of the per-block "Card colour" choice: `.has-card-theme-<theme id>` sets the card roles of the cards inside a block from THAT theme's own matrix (its
 * background becomes the card background, its heading, text, button and icon the card's), so one block can carry cards in another colour than its section.
 */
export function buildCardColourCss(colors, themeList) {
  const resolver = createResolver(colors);
  const decls = (m) => [
    `  --section-card-bg: ${resolver.css(m.bg)};`,
    `  --section-card-heading: ${cssValue(resolver, m.heading)};`,
    `  --section-card-text: ${cssValue(resolver, m.text)};`,
    `  --section-card-muted: ${cssValue(resolver, m.muted)};`,
    `  --section-card-border: ${cssValue(resolver, m.border)};`,
    `  --section-card-button-bg: ${cssValue(resolver, m.buttonBg)};`,
    `  --section-card-button-text: ${cssValue(resolver, m.buttonText)};`,
    `  --section-card-button-border: ${cssValue(resolver, m.buttonBorder)};`,
    `  --section-card-icon: ${cssValue(resolver, m.icon)};`,
    `  --section-card-eyebrow: ${cssValue(resolver, m.eyebrow)};`,
    `  --section-card-accent: ${cssValue(resolver, m.accent)};`,
  ];
  const out = ['/**', ' * The per-block "Card colour" choice (has-card-theme-*), generated from config/brand-palette.json; do not edit.', ' */'];
  out.push('[class*="has-card-theme-"] {', ...CARD_ALIASES, '}', '');
  for (const theme of themeList) out.push(`.has-card-theme-${theme.id} {`, ...decls(theme.matrix), '}', '');
  for (const theme of themeList) out.push(`.dark .has-card-theme-${theme.id} {`, ...decls(theme.dark), '}', '');
  return out.join('\n');
}

/** resources/generated/section-themes.php: the block styles the kit registers for core/group and sage/section. */
export function buildSectionThemesPhp(themeList, colors = []) {
  const roleOf = (name) => colors.find((color) => color.name === name)?.role;
  const rows = themeList.map((theme) => `    ['name' => '${theme.id}', 'label' => '${theme.label.replace(/'/g, "\\'")}', 'mode' => '${theme.mode}', 'colour' => '${theme.colour}', 'role' => ${roleOf(theme.colour) ? `'${roleOf(theme.colour)}'` : 'null'}],`);
  return ['<?php', '', '/**', ' * Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json.', ' * Do not edit directly. Read by the kit (app/section-themes.php) to register the Brand <Label> block styles.', ' */', '', 'return [', ...rows, '];', ''].join('\n');
}

/**
 * The editor's per-block palettes and the section-compatibility lists, from the editor swatches ([{ slug, value }], value a colour string)
 * and the themes. `button` is every swatch a theme uses for a button or its label; `text` is every swatch readable as text on a white page (4.5:1) plus white and the 50
 * steps (light text for dark sections); `compat[themeId]` lists the swatches readable on that theme's background (4.5:1), the
 * colours the matrix itself uses first, then the best-contrast rest. A reference with no swatch of its own (a colour's exact hex) maps
 * to the nearest swatch step; black and white are their own swatches.
 */
export function buildEditorData(colors, themeList, swatches) {
  const resolver = createResolver(colors);
  const swatchSteps = [50, 200, 400, 600, 800, 950]; // the steps the generator gives every colour in the editor palette (PALETTE_STEPS)
  const slugs = new Set(swatches.map((swatch) => swatch.slug));
  const white = colors.find((color) => color.role === 'white')?.name;
  const black = colors.find((color) => color.role === 'black')?.name;

  function swatchFor(spec) {
    const ref = normalise(spec).color;
    const parsed = resolver.parse(ref);
    if (!parsed || parsed.kind === 'transparent' || parsed.kind === 'hex') return null;
    if (parsed.kind === 'exact') return slugs.has(parsed.name) ? parsed.name : slugs.has(`${parsed.name}-600`) ? `${parsed.name}-600` : null;
    const step = swatchSteps.reduce((best, candidate) => (Math.abs(candidate - parsed.step) < Math.abs(best - parsed.step) ? candidate : best), swatchSteps[0]);
    return slugs.has(`${parsed.name}-${parsed.step}`) ? `${parsed.name}-${parsed.step}` : slugs.has(`${parsed.name}-${step}`) ? `${parsed.name}-${step}` : slugs.has(parsed.name) ? parsed.name : null;
  }

  const valueOf = (slug) => swatches.find((swatch) => swatch.slug === slug).value;
  const whiteValue = white ? resolver.value(white) : '#ffffff';
  const blackValue = black ? resolver.value(black) : '#000000';
  const button = new Set([white, black].filter(Boolean));
  for (const theme of themeList) {
    for (const matrix of [theme.matrix, theme.dark]) {
      for (const role of ['buttonBg', 'buttonText', 'buttonAltBg', 'buttonAltText']) {
        const slug = swatchFor(matrix[role]);
        if (slug) button.add(slug);
      }
    }
  }
  // readable as text on a white page (4.5:1), or a very light colour for dark sections (white and the 50 steps)
  const text = swatches
    .filter((swatch) => wcagContrast(swatch.value, whiteValue) >= 4.5 || swatch.slug === white || (/-50$/.test(swatch.slug) && wcagContrast(swatch.value, blackValue) >= 4.5))
    .map((swatch) => swatch.slug);

  const compat = {};
  for (const theme of themeList) {
    const bg = resolver.value(theme.matrix.bg);
    const own = ['text', 'heading', 'link', 'icon', 'buttonBg', 'buttonText'].map((role) => swatchFor(theme.matrix[role])).filter(Boolean);
    const rest = swatches
      .map((swatch) => ({ slug: swatch.slug, ratio: wcagContrast(swatch.value, bg) }))
      .filter((row) => row.ratio >= 4.5)
      .sort((a, b) => b.ratio - a.ratio)
      .map((row) => row.slug);
    compat[theme.id] = [...new Set([...own.filter((slug) => wcagContrast(valueOf(slug), bg) >= 3), ...rest])];
  }

  // the choices of the "Card colour" and "Card button colour" settings: every theme by label; per card background, the swatches a card button can be (4.5:1 for its
  // label, 3:1 against the card, and not the card's own hue)
  const labelFor = (slug) => swatches.find((swatch) => swatch.slug === slug)?.name ?? slug;
  const buttonsOn = (bgValue, ownerName) => swatches
    .filter((swatch) => !/^neutral-/.test(swatch.slug) && swatch.slug.replace(/-\d+$/, '') !== ownerName)
    .filter((swatch) => wcagContrast(swatch.value, bgValue) >= 3 && Math.max(wcagContrast(swatch.value, whiteValue), wcagContrast(swatch.value, blackValue)) >= 4.5)
    .map((swatch) => ({ value: swatch.slug, label: swatch.name }));
  const cardButtons = { auto: buttonsOn(whiteValue, white) };
  for (const theme of themeList) cardButtons[theme.id] = buttonsOn(resolver.value(theme.matrix.bg), theme.colour);

  return { button: [...button].filter((slug) => slugs.has(slug)), text, compat, themeOptions: themeList.map((theme) => ({ value: theme.id, label: theme.label })), cardButtons, labelFor };
}

export function buildEditorPalettesPhp(data, editorHidden = []) {
  const list = (items) => `[${items.map((item) => `'${item}'`).join(', ')}]`;
  return ['<?php', '', '/**', ' * Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json.', ' * Do not edit directly. Read by the platform\'s editor-settings module: the swatches a Button block and the text blocks offer, and (editorHidden) the keepSwatches only Administrators are offered.', ' */', '', 'return [', `    'button' => ${list(data.button)},`, `    'text' => ${list(data.text)},`, ...(editorHidden.length ? [`    'editorHidden' => ${list(editorHidden)},`] : []), '];', ''].join('\n');
}

export function buildSectionCompatJs(data) {
  return ['/**', ' * Generated by bin/generate-theme-tokens.mjs from config/brand-palette.json.', ' * Do not edit directly. SECTION_COMPAT: section theme id => the editor swatches readable on it, best first (resources/js/section-colors.js).', ' * SECTION_THEME_OPTIONS and CARD_BUTTON_OPTIONS feed the Card colour and Card button colour settings of the blocks with cards.', ' */', `export const SECTION_COMPAT = ${JSON.stringify(data.compat, null, 2)};`, `export const SECTION_THEME_OPTIONS = ${JSON.stringify(data.themeOptions, null, 2)};`, `export const CARD_BUTTON_OPTIONS = ${JSON.stringify(data.cardButtons, null, 2)};`, ''].join('\n');
}
