// The pure parts of resources/js/shop-filters.js (WP-89): the module wires the page only when a document exists, so Node can import it.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { pendingCount, statusText, withCount } from '../../resources/js/shop-filters.js';

const box = (checked, defaultChecked) => ({ type: 'checkbox', checked, defaultChecked });
const price = (value, defaultValue) => ({ type: 'text', value, defaultValue });

test('nothing differs from the applied state: no pending change', () => {
  assert.equal(pendingCount([box(true, true), box(false, false), price('10', '10'), price('', '')]), 0);
  assert.equal(pendingCount([]), 0);
});

test('every ticked, unticked or edited field counts once against the rendered state', () => {
  assert.equal(pendingCount([box(true, false), box(false, true), box(true, true), price('25', ''), price('', '')]), 3);
});

test('a tick that is undone, and a price edited back, count as nothing', () => {
  assert.equal(pendingCount([box(false, false), price('10', '10')]), 0);
  assert.equal(pendingCount([price(' 10 ', '10'), price(null, undefined)]), 0);
});

test('the label and the status sentence carry the number', () => {
  assert.equal(withCount('Apply filters (%d)', 3), 'Apply filters (3)');
  const words = { none: '', one: '1 change.', many: '%d changes.' };
  assert.equal(statusText(0, words), '');
  assert.equal(statusText(1, words), '1 change.');
  assert.equal(statusText(4, words), '4 changes.');
});
