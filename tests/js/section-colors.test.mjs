// The section colour picker's pure parts: which section theme a block sits in, and the palette with the readable colours first.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sectionThemeOf, compatibleFirst } from '../../resources/js/section-colors-lib.js';

const compat = { 'brand-sage-light': ['slate-800', 'sage-950'], 'brand-sage': ['white'] };
const store = (tree) => ({ getBlockParents: (id) => tree[id].parents, getBlockAttributes: (id) => ({ className: tree[id].className }) });

test('finds the nearest ancestor styled "Brand <Label>" and ignores other styles and unknown themes', () => {
  const tree = { leaf: { parents: ['inner', 'outer'] }, inner: { className: 'is-style-outline' }, outer: { className: 'wp-block-sage-section is-style-brand-sage-light' } };
  assert.equal(sectionThemeOf(store(tree), 'leaf', compat), 'brand-sage-light');
  assert.equal(sectionThemeOf(store({ leaf: { parents: ['x'] }, x: { className: 'is-style-brand-unknown' } }), 'leaf', compat), null);
  assert.equal(sectionThemeOf(store({ leaf: { parents: [] } }), 'leaf', compat), null);
  // the nearest one wins when sections are nested
  const nested = { leaf: { parents: ['inner', 'outer'] }, inner: { className: 'is-style-brand-sage' }, outer: { className: 'is-style-brand-sage-light' } };
  assert.equal(sectionThemeOf(store(nested), 'leaf', compat), 'brand-sage');
});

test('puts the readable colours first, in their order, and keeps every swatch and the order of the rest', () => {
  const palette = ['white', 'sage-50', 'sage-950', 'slate-800', 'black'].map((slug) => ({ slug }));
  const sorted = compatibleFirst(palette, ['slate-800', 'sage-950']).map((swatch) => swatch.slug);
  assert.deepEqual(sorted, ['slate-800', 'sage-950', 'white', 'sage-50', 'black']);
  assert.deepEqual(compatibleFirst(palette, []).map((swatch) => swatch.slug), palette.map((swatch) => swatch.slug));
});
