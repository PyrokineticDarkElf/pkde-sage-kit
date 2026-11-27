// The theme's font definitions and the dev-server base fix, behaviour only: google() and local() are stubs that return what they were given.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { buildFontDefinitions, fontDevServerBasePathFix } from '../../bin/vite-fonts.mjs';

const google = (family, options) => ({ kind: 'google', family, ...options });
const local = (family, options) => ({ kind: 'local', family, ...options });
const palette = (fonts) => { const file = join(mkdtempSync(join(tmpdir(), 'pkde-fonts-')), 'brand-palette.json'); writeFileSync(file, JSON.stringify({ fonts })); return file; };

test('a plain string is a Google family with the default weights, an object can be local or Google with its own options', () => {
  const definitions = buildFontDefinitions({ google, local, paletteConfigPath: palette({ display: 'Poppins', sans: { family: 'Inter', weights: [300] }, mono: { source: 'local', family: 'Mono', src: 'resources/fonts/*.woff2' } }) });

  assert.deepEqual(definitions, [
    { kind: 'google', family: 'Poppins', alias: 'display', weights: [400, 500, 600, 700] },
    { kind: 'google', family: 'Inter', alias: 'sans', weights: [300] },
    { kind: 'local', family: 'Mono', alias: 'mono', src: 'resources/fonts/*.woff2' },
  ]);
});

test('no palette file or no fonts gives no definitions, and unset slots are skipped', () => {
  assert.deepEqual(buildFontDefinitions({ google, local, paletteConfigPath: '/nonexistent/brand-palette.json' }), []);
  assert.deepEqual(buildFontDefinitions({ google, local, paletteConfigPath: palette({}) }), []);
  assert.equal(buildFontDefinitions({ google, local, paletteConfigPath: palette({ sans: 'Inter' }) }).length, 1);
});

test('the base fix strips the base off the font route only', () => {
  const plugin = fontDevServerBasePathFix('/app/themes/sage/public/build/');
  const middlewares = [];
  plugin.configureServer({ middlewares: { use: (fn) => middlewares.push(fn) } });
  const run = (url) => { const req = { url }; middlewares[0](req, {}, () => {}); return req.url; };

  assert.equal(plugin.name, 'font-dev-server-base-path-fix');
  assert.equal(run('/app/themes/sage/public/build/__laravel_vite_plugin__/fonts/a.woff2'), '/__laravel_vite_plugin__/fonts/a.woff2');
  assert.equal(run('/app/themes/sage/public/build/resources/css/app.css'), '/app/themes/sage/public/build/resources/css/app.css');
  assert.equal(run(undefined), undefined);
});
