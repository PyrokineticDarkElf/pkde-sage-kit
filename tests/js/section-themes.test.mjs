// Section themes (WP-100): Brand <Label> and Brand <Label> (light) for every palette colour, role matrices, dark mode, labels and the
// contrast check. The lib is called directly for the maths; the real generator runs on a throwaway theme directory for the files.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createBuilder, labelOf, WEAK_BELOW } from '../../bin/lib/section-themes.mjs';

const generator = fileURLToPath(new URL('../../bin/generate-theme-tokens.mjs', import.meta.url));
const neutrals = [{ name: 'black', hex: '#000000', role: 'black' }, { name: 'white', hex: '#ffffff', role: 'white' }];
const bb = [{ name: 'sage', hex: '#637972', role: 'brand', label: 'Sage' }, { name: 'blush', hex: '#eac3bf', role: 'accent' }, { name: 'slate', hex: '#4d656b' }, ...neutrals];

function run(colors, extra = {}, args = []) {
  const root = mkdtempSync(join(tmpdir(), 'pkde-kit-'));
  const theme = join(root, 'web/app/themes/sage');
  mkdirSync(join(root, 'config'), { recursive: true });
  mkdirSync(theme, { recursive: true });
  writeFileSync(join(root, 'config/brand-palette.json'), JSON.stringify({ colors, fonts: { display: 'Poppins', sans: 'Inter' }, ...extra }));
  writeFileSync(join(theme, 'theme.json'), '{\n  "settings": {\n    "color": {\n      "palette": [\n      ]\n    }\n  }\n}\n');
  const result = spawnSync('node', [generator, ...args], { cwd: theme, env: { ...process.env, PKDE_THEME_DIR: theme }, encoding: 'utf8' });
  const read = (file) => { try { return readFileSync(join(theme, file), 'utf8'); } catch { return ''; } };
  return { ...result, theme, read, css: read('resources/css/generated/section-themes.css'), php: read('resources/generated/section-themes.php'), themeJson: read('theme.json'), options: read('resources/js/generated/theme-options.js'), root };
}

const block = (css, selector) => css.match(new RegExp(`^${selector.replace(/[.]/g, '\\.')} \\{[^}]*\\}`, 'm'))?.[0] ?? '';

test('a colour label is optional, never changes the slug, and names the swatches, the theme picker and the section themes', () => {
  assert.equal(labelOf({ name: 'dusty-rose' }), 'Dusty Rose');
  assert.equal(labelOf({ name: 'x', label: ' Sage ' }), 'Sage');
  const r = run(bb);
  assert.equal(r.status, 0, r.stderr);
  assert.match(r.themeJson, /"slug": "sage-600", "name": "Sage 600"/);
  assert.match(r.themeJson, /"slug": "blush-600", "name": "Blush 600"/); // no label: the name, title-cased
  assert.match(r.options, /"value": "sage",\s+"label": "Sage"/);
  assert.match(r.php, /'name' => 'brand-sage', 'label' => 'Brand Sage'/);
  assert.match(r.php, /'name' => 'brand-sage-light', 'label' => 'Brand Sage \(light\)'/);
  const labelled = run([{ ...bb[0], label: 'Moss' }, ...bb.slice(1)]);
  assert.match(labelled.php, /'name' => 'brand-sage', 'label' => 'Brand Moss'/); // the label changed, the name (the class) did not
  assert.match(labelled.css, /\.is-style-brand-sage \{/);
});

test('every colour gets a solid and a light theme (white has no light one), and the older theme-* classes keep their variables and gain the roles', () => {
  const r = run(bb);
  for (const colour of ['sage', 'blush', 'slate', 'black']) {
    assert.ok(block(r.css, `.is-style-brand-${colour}`), `solid ${colour}`);
    assert.ok(block(r.css, `.is-style-brand-${colour}-light`), `light ${colour}`);
  }
  assert.ok(block(r.css, '.is-style-brand-white'));
  assert.equal(block(r.css, '.is-style-brand-white-light'), '');
  const old = r.css.match(/\.theme-sage,\n\.theme-alpha \{[^}]*\}/)?.[0] ?? '';
  assert.match(old, /--pkde-theme-background: var\(--color-sage-600\);/); // unchanged: the 600 step
  assert.match(old, /--section-bg: var\(--pkde-theme-background\);/);
  const solid = block(r.css, '.is-style-brand-sage');
  for (const role of ['bg', 'heading', 'text', 'muted', 'link', 'button-bg', 'button-text', 'button-alt-bg', 'button-alt-text', 'icon', 'border']) assert.match(solid, new RegExp(`--section-${role}:`), role);
  assert.match(solid, /--section-bg: var\(--color-sage-exact\);/); // the colour itself
  assert.match(block(r.css, '.is-style-brand-sage-light'), /--section-bg: var\(--color-sage-100\);/); // its tint
  assert.match(solid, /--pkde-theme-background: var\(--section-bg\);/); // the kit's blocks follow it through the older tokens
});

test('dark mode: a matrix per theme under .dark; a light theme goes to its 900 shade, a solid one keeps its colour', () => {
  const r = run(bb);
  assert.match(block(r.css, '.dark .is-style-brand-slate-light'), /--section-bg: var\(--color-slate-900\);/);
  assert.match(block(r.css, '.dark .is-style-brand-slate'), /--section-bg: var\(--color-slate-exact\);/);
  assert.doesNotMatch(r.css, /prefers-color-scheme/); // only the site's own switch, so a site without dark mode is never flipped
});

test('declared values win: roles, the light background and the dark matrices', () => {
  const colors = bb.map((c) => (c.name === 'sage' ? { ...c, styles: { heading: 'slate-800', muted: { color: 'slate-800', opacity: 80 }, light: { background: 'sage-200', heading: 'slate-800', dark: { background: 'slate-950', text: 'white' } }, dark: { text: 'blush-50' } } } : c));
  const r = run(colors);
  assert.equal(r.status, 0, r.stderr);
  assert.match(block(r.css, '.is-style-brand-sage'), /--section-heading: var\(--color-slate-800\);/);
  assert.match(block(r.css, '.is-style-brand-sage'), /--section-muted: color-mix\(in srgb, var\(--color-slate-800\) 80%, transparent\);/);
  assert.match(block(r.css, '.is-style-brand-sage-light'), /--section-bg: var\(--color-sage-200\);/);
  assert.match(block(r.css, '.is-style-brand-sage-light'), /--section-heading: var\(--color-slate-800\);/);
  assert.match(block(r.css, '.dark .is-style-brand-sage'), /--section-text: var\(--color-blush-50\);/);
  assert.match(block(r.css, '.dark .is-style-brand-sage-light'), /--section-bg: var\(--color-slate-950\);/);
});

test('a role that is not a known colour reference stops generation with a message naming it', () => {
  const r = run(bb.map((c) => (c.name === 'sage' ? { ...c, styles: { heading: 'sagee-800' } } : c)));
  assert.notEqual(r.status, 0);
  assert.match(r.stderr, /sage: styles\.heading is not a known colour reference/);
});

test('the older styles keys are not read by the new themes, so an existing palette never turns red by adopting them', () => {
  const old = bb.map((c) => (c.name === 'sage' ? { ...c, styles: { background: 'sage-600', content: 'white-50', highlight: 'sage-200', buttonPrimaryBackground: 'white-50', buttonPrimaryText: 'black-950', buttonSecondaryBackground: 'transparent', buttonSecondaryText: 'white-50', border: 'sage-500', card: 'white' } } : c));
  const r = run(old);
  assert.equal(r.status, 0, r.stderr);
  assert.doesNotMatch(block(r.css, '.is-style-brand-sage'), /white-50|black-950/);
});

test('the autofilled matrices of a varied palette all reach their contrast thresholds, in both modes', () => {
  const palette = [
    { name: 'brand', hex: '#334155', role: 'brand' }, { name: 'accent', hex: '#64748b', role: 'accent' }, { name: 'gold', hex: '#eccd7c' }, { name: 'neon', hex: '#39ff14' },
    { name: 'magenta', hex: '#ed55ff' }, { name: 'navy', hex: '#002457' }, { name: 'sand', hex: '#efe9de' }, { name: 'teal', hex: '#38bbcf' }, ...bb.slice(0, 3).map((c) => ({ ...c, name: `x${c.name}`, role: undefined })), ...neutrals,
  ];
  const builder = createBuilder(palette);
  const result = builder.checkAll(builder.themes());
  assert.deepEqual(result.fails, []);
});

test('a weak colour (best text colour under the weak limit, no brand colour readable on it) uses one text colour for every text role and WARNs by name', () => {
  assert.equal(WEAK_BELOW, 5.2);
  const builder = createBuilder(bb);
  const sage = builder.themes().find((theme) => theme.id === 'brand-sage');
  assert.equal(sage.matrix.weak, true);
  for (const role of ['heading', 'text', 'muted', 'link', 'icon', 'buttonAltText']) assert.equal(sage.matrix[role], 'white', role);
  assert.equal(builder.themes().find((theme) => theme.id === 'brand-slate').matrix.weak, false); // 6.2:1 with white and a tint to draw with
  const r = run(bb);
  assert.match(r.stderr, /brand-sage: a weak colour\. Only white is readable on it/);
  assert.equal(r.status, 0); // generating never fails on it
});

test('theme:check FAILs a declared pair under its threshold and names it; acceptLowContrast turns it into a note', () => {
  const bad = bb.map((c) => (c.name === 'blush' ? { ...c, styles: { text: 'blush-400', link: 'blush-400' } } : c));
  const builder = createBuilder(bad);
  const result = builder.checkAll(builder.themes());
  assert.ok(result.fails.some((line) => /brand-blush: text blush-400 measures [0-9.]+:1 on the background/.test(line)), result.fails.join('\n'));
  assert.ok(result.fails.some((line) => /brand-blush: link blush-400/.test(line)));
  const accepted = builder.checkAll(builder.themes(), { accept: ['blush'] });
  assert.equal(accepted.fails.filter((line) => /brand-blush/.test(line)).length, 0);
  assert.ok(accepted.warns.some((line) => /accepted: acceptLowContrast/.test(line)));
  // and through the real generator: generate, then --check exits 1 listing it
  const r = run(bad);
  const checked = spawnSync('node', [generator, '--check'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(checked.status, 1);
  assert.match(checked.stderr, /brand-blush: text blush-400/);
});

test('the dark matrices are checked too', () => {
  const bad = bb.map((c) => (c.name === 'slate' ? { ...c, styles: { dark: { text: 'slate-800' } } } : c));
  const builder = createBuilder(bad);
  assert.ok(builder.checkAll(builder.themes()).fails.some((line) => /brand-slate \(dark\): text slate-800/.test(line)));
});

test('the editor gets per-block palettes (button swatches, text-safe swatches) and a readable-colours list per section theme', () => {
  const r = run(bb);
  const palettes = r.read('resources/generated/editor-palettes.php');
  const button = /'button' => \[([^\]]*)\]/.exec(palettes)[1];
  const text = /'text' => \[([^\]]*)\]/.exec(palettes)[1];
  assert.match(button, /'white'/);
  assert.match(button, /'sage-600'/); // a swatch a theme draws a button or its label in
  assert.doesNotMatch(button, /sage-200/); // a pale tint is not a button colour
  assert.match(text, /'slate-800'/);
  assert.match(text, /'sage-50'/); // light text for dark sections
  assert.doesNotMatch(text, /'sage-400'/); // mid tones are not text-safe
  const compat = r.read('resources/js/generated/section-compat.js');
  assert.match(compat, /"brand-sage": \[\s+"white"/); // white is what is readable on sage
  assert.match(compat, /"brand-sage-light": \[\s+"black"/);
});
