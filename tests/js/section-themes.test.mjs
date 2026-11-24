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

function run(colors, extra = {}, args = [], files = {}) {
  const root = mkdtempSync(join(tmpdir(), 'pkde-kit-'));
  const theme = join(root, 'web/app/themes/sage');
  mkdirSync(join(root, 'config'), { recursive: true });
  mkdirSync(theme, { recursive: true });
  writeFileSync(join(root, 'config/brand-palette.json'), JSON.stringify({ colors, fonts: { display: 'Poppins', sans: 'Inter' }, ...extra }));
  writeFileSync(join(theme, 'theme.json'), '{\n  "settings": {\n    "color": {\n      "palette": [\n      ]\n    }\n  }\n}\n');
  for (const [file, content] of Object.entries(files)) { mkdirSync(join(theme, file, '..'), { recursive: true }); writeFileSync(join(theme, file), content); }
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
  assert.match(r.php, /'name' => 'brand-sage', 'label' => 'Brand Sage', 'mode' => 'solid', 'colour' => 'sage', 'role' => 'brand'/);
  assert.match(r.php, /'name' => 'brand-slate', 'label' => 'Brand Slate', 'mode' => 'solid', 'colour' => 'slate', 'role' => null/);
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
  assert.doesNotMatch(old, /--section-bg:/); // the roles follow the values in ONE shared rule, not in every theme
  const shared = r.css.split('}').find((rule) => rule.includes('--section-bg: var(--pkde-theme-background);')) ?? '';
  assert.match(shared, /\.theme-sage,\n\.theme-alpha,\n\.theme-blush/);
  assert.match(shared, /--section-border: var\(--pkde-theme-border\);/);
  assert.equal(r.css.split('--section-bg: var(--pkde-theme-background);').length - 1, 1);
  const solid = block(r.css, '.is-style-brand-sage');
  for (const role of ['bg', 'heading', 'text', 'muted', 'link', 'button-bg', 'button-text', 'button-alt-bg', 'button-alt-text', 'icon', 'border']) assert.match(solid, new RegExp(`--section-${role}:`), role);
  assert.match(solid, /--section-bg: var\(--color-sage-exact\);/); // the colour itself
  assert.match(block(r.css, '.is-style-brand-sage-light'), /--section-bg: var\(--color-sage-100\);/); // its tint
  // the kit's blocks follow it through the older tokens: declared once for every Brand style, not in every theme
  assert.doesNotMatch(solid, /--pkde-theme-/);
  assert.match(r.css.match(/\[class\*="is-style-brand-"\] \{[^}]*\}/)?.[0] ?? '', /--pkde-theme-background: var\(--section-bg\);/);
  assert.equal(r.css.split('--pkde-theme-background: var(--section-bg);').length - 1, 1);
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

test('brand-colours.csv (--colors-csv) and --label give the palette its labels, roles and hex values; bin/setup uses both', () => {
  const root = mkdtempSync(join(tmpdir(), 'pkde-kit-csv-'));
  const theme = join(root, 'web/app/themes/sage');
  mkdirSync(join(root, 'config'), { recursive: true });
  mkdirSync(theme, { recursive: true });
  writeFileSync(join(theme, 'theme.json'), '{\n  "settings": {\n    "color": {\n      "palette": [\n      ]\n    }\n  }\n}\n');
  writeFileSync(join(root, 'brand-colours.csv'), '# the client\'s colours\nname,label,hex,role\nsage,Sage,#637972,brand\nblush,,#eac3bf,accent\nslate,Deep Slate,#4d656b,\n');
  const generate = (...args) => spawnSync('node', [generator, ...args], { cwd: theme, env: { ...process.env, PKDE_THEME_DIR: theme }, encoding: 'utf8' });
  const first = generate('--colors-csv', 'brand-colours.csv', '--label', 'blush=Blush Pink');
  assert.equal(first.status, 0, first.stderr);
  const palette = JSON.parse(readFileSync(join(root, 'config/brand-palette.json'), 'utf8'));
  assert.deepEqual(palette.colors.slice(0, 3).map((c) => [c.name, c.label, c.role]), [['sage', 'Sage', 'brand'], ['blush', 'Blush Pink', 'accent'], ['slate', 'Deep Slate', undefined]]); // --label beats an empty CSV label
  assert.match(readFileSync(join(theme, 'theme.json'), 'utf8'), /"name": "Deep Slate 600"/);
  assert.match(readFileSync(join(theme, 'resources/generated/section-themes.php'), 'utf8'), /'label' => 'Brand Deep Slate \(light\)'/);
  // the labels are kept the next time the generator runs from the saved palette
  assert.equal(generate().status, 0);
  assert.equal(JSON.parse(readFileSync(join(root, 'config/brand-palette.json'), 'utf8')).colors[2].label, 'Deep Slate');
  // a broken row stops with the row number
  writeFileSync(join(root, 'brand-colours.csv'), 'name,label,hex\nsage,Sage,not-a-hex\n');
  const bad = generate('--colors-csv', 'brand-colours.csv');
  assert.notEqual(bad.status, 0);
  assert.match(bad.stderr, /brand-colours\.csv row 2/);
});

test('the secondary button is a true outline, and the button edge shows only where the button barely shows against the section', () => {
  const themes = createBuilder(bb).themes();
  const slate = themes.find((t) => t.id === 'brand-slate').matrix;
  assert.equal(slate.buttonAltBg, 'transparent'); // no fill
  assert.equal(slate.buttonAltText, 'white'); // the text colour for the label and the edge
  assert.equal(slate.buttonBorder, slate.buttonBg); // visible already: the edge is invisible (its own colour)
  // blush-200 on sage-200 is 1.02:1: the edge becomes the label colour
  const declaredBb = bb.map((c) => (c.name === 'sage' ? { ...c, styles: { light: { background: 'sage-200', buttonBg: 'blush-200', buttonText: 'slate-950' } } } : c));
  const light = createBuilder(declaredBb).themes().find((t) => t.id === 'brand-sage-light').matrix;
  assert.equal(light.buttonBorder, 'slate-950');
  const kept = createBuilder(declaredBb.map((c) => (c.name === 'sage' ? { ...c, styles: { light: { ...c.styles.light, buttonBorder: 'sage-800' } } } : c))).themes().find((t) => t.id === 'brand-sage-light').matrix;
  assert.equal(kept.buttonBorder, 'sage-800'); // a declared edge wins
});

test('card roles: white cards on a solid or tinted section, the lightest brand tint on a white one, a sage or pink button chosen by contrast, declared values win', () => {
  const themes = createBuilder(bb).themes();
  const get = (id) => themes.find((t) => t.id === id);
  const sage = get('brand-sage').matrix;
  assert.equal(sage.cardBg, 'white');
  assert.equal(get('brand-blush-light').matrix.cardBg, 'white');
  assert.equal(get('brand-white').matrix.cardBg, 'sage-50'); // the brand colour's lightest tint
  assert.equal(sage.cardButtonBg, 'sage'); // pink fails 4.5:1 on white; the brand colour passes
  assert.notEqual(sage.cardButtonBg, sage.cardBg);
  assert.match(String(get('brand-sage-light').dark.cardBg), /^sage-(700|800|950)$/); // dark mode: a deep shade, not a white card on a dark section
  const declared = createBuilder(bb.map((c) => (c.name === 'sage' ? { ...c, styles: { card: { background: 'blush-50', buttonBg: 'slate' } } } : c))).themes().find((t) => t.id === 'brand-sage').matrix;
  assert.equal(declared.cardBg, 'blush-50');
  assert.equal(declared.cardButtonBg, 'slate');
  assert.match(declared.cardText, /black|slate-950|white/); // worked out for the declared card background
  // the older string `card` pointer is not a card matrix
  assert.equal(createBuilder(bb.map((c) => (c.name === 'sage' ? { ...c, styles: { card: 'white' } } : c))).themes()[0].matrix.cardBg, 'white');
});

test('every card pair of every theme, in both modes, passes on a varied palette, and a card pair under its threshold FAILs by name', () => {
  const palette = [
    { name: 'brand', hex: '#334155', role: 'brand' }, { name: 'accent', hex: '#64748b', role: 'accent' }, { name: 'gold', hex: '#eccd7c' }, { name: 'neon', hex: '#39ff14' },
    { name: 'navy', hex: '#002457' }, { name: 'sand', hex: '#efe9de' }, { name: 'teal', hex: '#38bbcf' }, ...bb.slice(0, 3).map((c) => ({ ...c, name: `x${c.name}`, role: undefined })), ...neutrals,
  ];
  const b = createBuilder(palette);
  assert.deepEqual(b.checkAll(b.themes()).fails.filter((line) => /card/.test(line)), []);
  const bad = createBuilder(bb.map((c) => (c.name === 'sage' ? { ...c, styles: { card: { background: 'white', text: 'blush' } } } : c)));
  assert.ok(bad.checkAll(bad.themes()).fails.some((line) => /brand-sage: card text blush measures [0-9.]+:1 on the card/.test(line)));
});

test('the generated CSS carries the card roles, the button edge, and points the kit\'s card tokens at them', () => {
  const css = run(bb).css;
  const sage = block(css, '.is-style-brand-sage');
  for (const role of ['card-bg', 'card-heading', 'card-text', 'card-muted', 'card-border', 'card-button-bg', 'card-button-text', 'card-button-border', 'card-icon', 'button-border']) assert.match(sage, new RegExp(`--section-${role}:`), role);
  assert.doesNotMatch(sage, /--pkde-/);
  assert.match(css.match(/\[class\*="is-style-brand-"\] \{[^}]*\}/)?.[0] ?? '', /--pkde-card-background: var\(--section-card-bg\);[^}]*--pkde-card-accent: var\(--section-card-heading\);/s);
  assert.match(css.match(/\[class\*="is-style-brand-"\] \{[^}]*\}/)?.[0] ?? '', /--pkde-theme-btn-border: var\(--section-button-border\);/);
  assert.match(css.match(/\[class\*="has-card-theme-"\] \{[^}]*\}/)?.[0] ?? '', /--pkde-card-background: var\(--section-card-bg\);/); // a per-block card colour re-points them on its own element
  assert.match(block(css, '.dark .is-style-brand-sage-light'), /--section-card-bg: var\(--color-sage-(700|800|950)\);/);
});

test('links get their own colour where one works (4.5:1 on the background, 3:1 against the text); otherwise they are the text colour and underlined', () => {
  const css = run(bb).css;
  const link = (selector) => block(css, selector).match(/--section-link: var\(--color-([a-z0-9-]+)\)/)?.[1];
  const text = (selector) => block(css, selector).match(/--section-text: var\(--color-([a-z0-9-]+)\)/)?.[1];
  assert.equal(link('.is-style-brand-black'), 'sage-exact'); // white text, a sage link: both readable and 3:1 apart
  assert.equal(link('.is-style-brand-sage'), text('.is-style-brand-sage')); // nothing works on sage: the text colour
  const rule = css.match(/:is\(([^)]*)\)\[class\*="is-style-brand-"\]\[class\*="is-style-brand-"\] :is\(p, li, dt, dd, td, th, blockquote, cite\) a:where\([^{]*\{\s*text-decoration: underline;/)?.[1] ?? '';
  assert.match(rule, /\.is-style-brand-sage\b(?!-)/); // underlined
  assert.doesNotMatch(rule, /\.is-style-brand-black\b(?!-)/); // its own colour: no forced underline
  assert.match(css, /:not\(\.wp-block-button__link, \.pkde-btn, \.pkde-btn-alt, \.wp-element-button\)\)/); // buttons are left alone
  assert.match(css, /nav,/); // and nav links
});

test('a declared link wins, and a declared link too close to the text is a warning, not a failure', () => {
  const colors = bb.map((c) => (c.name === 'sage' ? { ...c, styles: { link: 'blush-200' } } : c));
  const r = run(colors);
  assert.equal(r.status, 0, r.stderr);
  assert.match(block(r.css, '.is-style-brand-sage'), /--section-link: var\(--color-blush-200\);/);
});

test('the palette is carried into the built theme.json (what WordPress reads in dev), the rest of it untouched; without one the generator says to build', () => {
  const built = JSON.stringify({ __processed__: 'x', settings: { color: { palette: [{ slug: 'old', color: '#000' }], custom: false }, typography: { fontFamilies: [{ slug: 'sans' }] } } });
  const r = run(bb, {}, [], { 'public/build/assets/theme.json': built });
  assert.equal(r.status, 0, r.stderr);
  const after = JSON.parse(r.read('public/build/assets/theme.json'));
  assert.equal(after.settings.color.palette.some((swatch) => swatch.slug === 'sage-600'), true);
  assert.equal(after.settings.color.palette.some((swatch) => swatch.slug === 'old'), false);
  assert.equal(after.settings.color.custom, false);
  assert.deepEqual(after.settings.typography, { fontFamilies: [{ slug: 'sans' }] });
  assert.match(r.stdout, /Updated public\/build\/assets\/theme\.json/);
  assert.match(run(bb).stdout, /No public\/build\/assets\/theme\.json yet/);
});

// keepSwatches (TPL-249): exact colours in the editor palette that are not brand colours.
const keep = [
  { slug: 'peach', hex: '#F5EFE9', label: 'Peach' },
  { slug: 'sage-tint', hex: '#eff1ef', label: 'Sage tint', editorVisible: true },
  { slug: 'stone', hex: '#f4f1ec', label: 'Stone' },
];

test('keepSwatches are appended to theme.json after the generated swatches, and nowhere else', () => {
  const plain = run(bb);
  const r = run(bb, { keepSwatches: keep });
  assert.equal(r.status, 0, r.stderr);
  const palette = JSON.parse(r.themeJson).settings.color.palette;
  const base = JSON.parse(plain.themeJson).settings.color.palette;
  assert.deepEqual(palette.slice(0, base.length), base); // the generated ones are unchanged and first
  assert.deepEqual(palette.slice(base.length), [
    { slug: 'peach', name: 'Peach', color: '#f5efe9' },
    { slug: 'sage-tint', name: 'Sage tint', color: '#eff1ef' },
    { slug: 'stone', name: 'Stone', color: '#f4f1ec' },
  ]);
  // not a colour of the palette: no section theme, no ladder, no role auto-fill, not in the button or text palettes
  assert.doesNotMatch(r.php, /peach|stone/);
  assert.doesNotMatch(r.css, /peach|stone/);
  assert.doesNotMatch(r.read('resources/css/generated/theme-tokens.css'), /peach|stone/);
  assert.doesNotMatch(r.read('resources/js/generated/section-compat.js'), /peach|stone/);
  const editor = r.read('resources/generated/editor-palettes.php');
  assert.doesNotMatch(editor, /'(button|text)' => \[[^\]]*(peach|stone)/);
  // only Administrators get the ones without editorVisible: the platform hides these from the editor
  assert.match(editor, /'editorHidden' => \['peach', 'stone'\]/);
  assert.equal(plain.read('resources/generated/editor-palettes.php').includes("'editorHidden' =>"), false);
  // the config keeps them, normalised
  assert.equal(JSON.parse(readFileSync(join(r.root, 'config/brand-palette.json'), 'utf8')).keepSwatches[0].hex, '#f5efe9');
});

test('keepSwatches: --check is clean and notes each swatch; a stale theme.json is reported', () => {
  const r = run(bb, { keepSwatches: keep });
  const check = spawnSync('node', [generator, '--check'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(check.status, 0, check.stderr);
  assert.match(check.stderr, /keepSwatches "peach" \(#f5efe9\).*Only Administrators can pick it/);
  assert.match(check.stderr, /keepSwatches "sage-tint".*Editors can pick it/);
  writeFileSync(join(r.theme, 'theme.json'), r.themeJson.replace('"slug": "stone"', '"slug": "stoned"'));
  const stale = spawnSync('node', [generator, '--check'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(stale.status, 1);
  assert.match(stale.stderr, /settings\.color\.palette/);
});

test('keepSwatches are validated: slug, hex, label, no clash with a colour or a generated swatch', () => {
  const bad = (entry, pattern) => { const r = run(bb, { keepSwatches: [entry] }); assert.equal(r.status, 1, JSON.stringify(entry)); assert.match(r.stderr, pattern); };
  bad({ slug: 'Peach', hex: '#f5efe9', label: 'x' }, /slug must be lowercase/);
  bad({ slug: 'peach', hex: '#f5e', label: 'x' }, /6-digit hex/);
  bad({ slug: 'peach', hex: '#f5efe9', label: ' ' }, /needs a label/);
  bad({ slug: 'peach', hex: '#f5efe9', label: 'x', editorVisible: 'yes' }, /editorVisible must be true or false/);
  bad({ slug: 'sage', hex: '#f5efe9', label: 'x' }, /already a palette colour/);
  bad({ slug: 'sage-600', hex: '#f5efe9', label: 'x' }, /already a palette colour/);
  bad({ slug: 'neutral-100', hex: '#f5efe9', label: 'x' }, /already a palette colour/);
  const twice = run(bb, { keepSwatches: [keep[0], keep[0]] });
  assert.equal(twice.status, 1);
  assert.match(twice.stderr, /duplicate slug "peach"/);
});

test('keepSwatches survive a regenerate from --color arguments', () => {
  const r = run(bb, { keepSwatches: keep });
  const again = spawnSync('node', [generator, '--color', 'sage=#637972', '--color', 'blush=#eac3bf', '--color', 'slate=#4d656b'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(again.status, 0, again.stderr);
  assert.match(readFileSync(join(r.theme, 'theme.json'), 'utf8'), /"slug": "stone"/);
});

// Shape tokens (TPL-266, WP-108 A)
test('shape: soft is the default and writes the tokens, the derived Tailwind scale and shape.php; sharp and round change them; a bad value stops the generator', () => {
  const soft = run(bb);
  assert.equal(soft.status, 0, soft.stderr);
  const css = soft.read('resources/css/generated/brand-tokens.css');
  assert.match(css, /:root \{\n  --pkde-radius-sm: 0\.5rem;\n  --pkde-radius-md: 1rem;\n  --pkde-radius-lg: 2rem;\n  --pkde-radius-full: 9999px;\n  --pkde-border-thin: 1px;\n  --pkde-border-thick: 2px;\n\}/);
  assert.match(css, /@theme static \{\n  --radius-xs: calc\(var\(--pkde-radius-sm\) \* 0\.25\);/);
  assert.match(css, /--radius-4xl: calc\(var\(--pkde-radius-lg\) \* 1\);/);
  assert.match(soft.read('resources/generated/shape.php'), /'preset' => 'soft'/);
  assert.equal(JSON.parse(readFileSync(join(soft.root, 'config/brand-palette.json'), 'utf8')).shape, undefined); // no `shape` key stays no key

  const sharp = run(bb, { shape: { preset: 'sharp' } });
  assert.match(sharp.read('resources/css/generated/brand-tokens.css'), /--pkde-radius-sm: 0px;/);
  assert.match(sharp.read('resources/css/generated/brand-tokens.css'), /--pkde-radius-full: 0px;/);

  const round = run(bb, { shape: { preset: 'round', radius: { full: '9999px' }, border: { thick: '3px' } } });
  assert.match(round.read('resources/css/generated/brand-tokens.css'), /--pkde-radius-md: 1\.5rem;/);
  assert.match(round.read('resources/css/generated/brand-tokens.css'), /--pkde-border-thick: 3px;/);

  for (const [shape, pattern] of [[{ preset: 'pointy' }, /shape\.preset must be one of/], [{ radius: { sm: 'big' } }, /must be a length/], [{ radius: { xl: '1rem' } }, /is not a token/], [{ border: { thin: '1' } }, /must be a length/]]) {
    const bad = run(bb, { shape });
    assert.equal(bad.status, 1, JSON.stringify(shape));
    assert.match(bad.stderr, pattern);
  }
});

test('shape: --check notices a changed preset and an old shape.php', () => {
  const r = run(bb, { shape: { preset: 'round' } });
  const check = spawnSync('node', [generator, '--check'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(check.status, 0, check.stderr);
  writeFileSync(join(r.theme, 'resources/generated/shape.php'), '<?php return [];');
  const stale = spawnSync('node', [generator, '--check'], { cwd: r.theme, env: { ...process.env, PKDE_THEME_DIR: r.theme }, encoding: 'utf8' });
  assert.equal(stale.status, 1);
  assert.match(stale.stderr, /shape\.php/);
});
