#!/usr/bin/env node
/**
 * Writes ONE plain stylesheet of a site's theme: every variable and the section themes with their role selectors, from the files `npm run theme:generate` produced.
 *
 *   npm run theme:export                         (in the theme folder: writes pkde-theme.css there)
 *   node vendor/pkde/sage-kit/bin/theme-export.mjs [--theme-dir <theme folder>] [--out <file>]
 *
 * The generated tokens are Tailwind source (`@theme { ... }`, `@source inline(...)`); a plain stylesheet needs the same custom properties on `:root`, so this converts them.
 * The result re-skins anything that is written on role classes (`is-style-role-brand`, ...), for example the pattern library's previews, without the site's whole build. A web
 * font named in the palette gets an @font-face that uses the font when it is installed on the viewer's computer (`src: local()`), else the system font.
 */
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const GENERATED = ['brand-tokens.css', 'section-themes.css', 'card-themes.css'];

const plain = (text) => text.replace(/@theme(?: static)? \{/g, ':root {').replace(/^@source inline\(.*\);?$/gm, '');
const familyOf = (font) => (typeof font === 'string' ? font : font?.family ?? '');

/** The stylesheet text of the theme in `themeDir` (a theme folder whose generated CSS exists; its palette is read from the project's config/). */
export function buildThemeCss(themeDir, label = 'this site') {
  const read = (file) => {
    const path = join(themeDir, 'resources/css/generated', file);
    return existsSync(path) ? readFileSync(path, 'utf8') : '';
  };

  if (read('section-themes.css') === '') throw new Error(`No generated CSS in ${themeDir}/resources/css/generated: run npm run theme:generate first.`);

  const palette = join(themeDir, '../../../../config/brand-palette.json');
  const fonts = existsSync(palette) ? JSON.parse(readFileSync(palette, 'utf8')).fonts ?? {} : {};
  const families = [['display', fonts.display, 'serif'], ['sans', fonts.sans, 'sans-serif'], ['mono', fonts.mono, 'monospace']].filter(([, font]) => familyOf(font));
  const faces = [...new Set(families.map(([, font]) => familyOf(font)))].map((family) => `@font-face { font-family: "${family}"; font-weight: 100 900; src: local("${family}"); }`);
  const tokens = families.map(([name, font, generic]) => `  --font-${name}: "${familyOf(font)}", ui-${generic}, ${generic};`);

  return [`/* The theme of ${label}: written by theme-export.mjs from the generated CSS of the theme. Role classes (is-style-role-*) take these colours. */`, ...faces, `:root {\n${tokens.join('\n')}\n}`, ...GENERATED.map((file) => plain(read(file)))].join('\n');
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  const arg = (name, fallback) => { const i = process.argv.indexOf(`--${name}`); return i > -1 ? process.argv[i + 1] : fallback; };
  const themeDir = resolve(arg('theme-dir', process.env.PKDE_THEME_DIR || process.cwd()));
  const out = resolve(arg('out', join(themeDir, 'pkde-theme.css')));

  try {
    const css = buildThemeCss(themeDir);
    writeFileSync(out, css);
    process.stdout.write(`${out}  ${(css.length / 1024).toFixed(1)} KB\n`);
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    process.exit(1);
  }
}
