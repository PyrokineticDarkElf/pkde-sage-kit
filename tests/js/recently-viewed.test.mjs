// The pure parts of resources/js/recently-viewed.js (TPL-134): the module wires the page only when a document exists, so Node can import it.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseIds, recordView } from '../../resources/js/recently-viewed.js';

test('a stored list is read defensively: bad JSON, wrong types and duplicates leave only product ids', () => {
  assert.deepEqual(parseIds('[5, 9, 5, "12", 0, -1, "x", 2.5]'), [5, 9, 12]);
  assert.deepEqual(parseIds('not json'), []);
  assert.deepEqual(parseIds('{"a":1}'), []);
  assert.deepEqual(parseIds(null), []);
  assert.deepEqual(parseIds('[1,2,3,4]', 2), [1, 2]);
});

test('viewing a product puts it first, once, and keeps the list at the cap', () => {
  assert.deepEqual(recordView([3, 2, 1], 2), [2, 3, 1]);
  assert.deepEqual(recordView([], 7), [7]);
  assert.deepEqual(recordView([5, 4, 3], 9, 3), [9, 5, 4]);
});
