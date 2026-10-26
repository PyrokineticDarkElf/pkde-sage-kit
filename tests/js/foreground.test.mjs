// The token generator emits a foreground token for every step of every colour (--color-brand-600-fg ...): white when it reaches 4.5:1
// on that step, otherwise whichever of white and near-black measures higher. Runs the real generator on a throwaway theme directory.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { wcagContrast } from 'culori';

const generator = fileURLToPath(new URL('../../bin/generate-theme-tokens.mjs', import.meta.url));

function generate(colors) {
  const root = mkdtempSync(join(tmpdir(), 'pkde-kit-'));
  const theme = join(root, 'web/app/themes/sage');
  mkdirSync(join(root, 'config'), { recursive: true });
  mkdirSync(theme, { recursive: true });
  writeFileSync(join(root, 'config/brand-palette.json'), JSON.stringify({ colors, fonts: { display: 'Poppins', sans: 'Inter' } }));
  writeFileSync(join(theme, 'theme.json'), '{\n  "settings": {\n    "color": {\n      "palette": [\n      ]\n    }\n  }\n}\n');
  const run = (...args) => execFileSync('node', [generator, ...args], { env: { ...process.env, PKDE_THEME_DIR: theme }, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  run();

  return { css: readFileSync(join(theme, 'resources/css/generated/brand-tokens.css'), 'utf8'), run };
}

const tokens = (css) => Object.fromEntries([...css.matchAll(/--color-([a-z0-9-]+): ([^;]+);/g)].map((m) => [m[1], m[2]]));
const COLORS = [
  { name: 'brand', hex: '#7c988f', role: 'brand' },
  { name: 'accent', hex: '#edb500', role: 'accent' },
  { name: 'ink', hex: '#334155' },
];

test('every step of every normal colour has a foreground token, and it is readable on that step', () => {
  const t = tokens(generate(COLORS).css);

  for (const name of ['brand', 'accent', 'ink', 'neutral']) {
    for (const step of [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]) {
      const background = t[`${name}-${step}`];
      const foreground = t[`${name}-${step}-fg`];
      assert.ok(foreground, `--color-${name}-${step}-fg is missing`);
      assert.match(foreground, /^#(ffffff|0a0a0a)$/);

      const best = Math.max(wcagContrast(background, '#ffffff'), wcagContrast(background, '#0a0a0a'));
      const got = wcagContrast(background, foreground);
      // White whenever it reaches 4.5:1 (the look the kit always had); otherwise the better of white and near-black.
      if (wcagContrast(background, '#ffffff') >= 4.5) assert.equal(foreground, '#ffffff', `${name}-${step}`);
      else assert.ok(Math.abs(got - best) < 1e-9, `${name}-${step}: ${foreground} is not the better text colour`);
    }
  }
});

test('a light brand colour gets a dark foreground on its 600 step, a dark one white', () => {
  const t = tokens(generate(COLORS).css);

  assert.equal(t['brand-600-fg'], '#0a0a0a', 'light #7c988f needs dark text');
  assert.equal(t['accent-600-fg'], '#0a0a0a', 'amber needs dark text');
  assert.equal(t['ink-600-fg'], '#ffffff');
  assert.equal(t['brand-fg'], t['brand-600-fg']);
  assert.equal(t['action-fg'], t['brand-600-fg']);
  assert.equal(t['highlight-fg'], t['accent-600-fg']);
});

test('the foreground is never worse than 4.5:1 on a step where either white or near-black can reach it', () => {
  const t = tokens(generate(COLORS).css);

  for (const [name, value] of Object.entries(t)) {
    const m = name.match(/^(brand|accent|ink|neutral)-(\d+)$/);
    if (!m) continue;
    const reachable = Math.max(wcagContrast(value, '#ffffff'), wcagContrast(value, '#0a0a0a')) >= 4.5;
    if (reachable) assert.ok(wcagContrast(value, t[`${name}-fg`]) >= 4.5, `${name} has no readable foreground`);
  }
});
