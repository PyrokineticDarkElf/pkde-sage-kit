/**
 * Colour maths shared by bin/generate-theme-tokens.mjs and bin/lib/section-themes.mjs: the shade ladder (50..950) every palette colour
 * gets, and the WCAG contrast function. Pure functions, importable from tests. The generator imports these; nothing else changed.
 */
import { converter, wcagContrast } from 'culori';

export { wcagContrast };
export const toOklch = converter('oklch');
export const toRgb = converter('rgb');

export const shadeLightness = { 50: 0.97, 100: 0.94, 200: 0.88, 300: 0.8, 400: 0.71, 500: 0.62, 700: 0.46, 800: 0.36, 900: 0.27, 950: 0.18 };
export const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

// The 600 step normally *is* the exact source colour (distance 0 below), so it reads back precisely what was typed in. But a
// bright/light source (a saturated yellow, say) can have a natural lightness above 500's fixed 0.62 — left unclamped, 600 would
// then render lighter than 500, breaking the scale's monotonic darkening (confirmed on #eab308). Clamping keeps 600 exactly on
// the source for every colour that was already well-behaved, and only pulls outliers back into a band strictly between 500 and 700.
export const SIX_HUNDRED_MIN = shadeLightness[700] + 0.01;
export const SIX_HUNDRED_MAX = shadeLightness[500] - 0.01;

/** One step of a colour's ladder, as an oklch() string (wcagContrast reads CSS colour strings directly). */
export function shade(color, step) {
  const base = toOklch(color.hex);
  if (!base) throw new Error(`Unable to convert ${color.hex}.`);
  const lightness = step === 600
    ? Math.min(SIX_HUNDRED_MAX, Math.max(SIX_HUNDRED_MIN, base.l))
    : shadeLightness[step];
  // Chroma falls toward both white and black, reducing out-of-gamut colours while preserving the supplied hue and the exact
  // source wherever a step's lightness matches the source's own (always true at 600, unless clamped above).
  const distance = Math.abs(lightness - base.l);
  const chroma = Math.max(0, base.c * (1 - distance * 1.15));
  // culori omits `h` for a fully achromatic source (pure #000000/#ffffff); hue is meaningless without chroma.
  const hue = base.h ?? 0;
  return `oklch(${lightness.toFixed(3)} ${chroma.toFixed(3)} ${hue.toFixed(2)})`;
}
