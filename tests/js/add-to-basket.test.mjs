// The pure parts of resources/js/add-to-basket.js and resources/js/product-carousel.js (MGR-79): the module exports them and wires the page only when
// a document exists, so Node can import it.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { addedMessage, isBusy, productName, simpleProductPayload } from '../../resources/js/add-to-basket.js';
import { navState, stepFor } from '../../resources/js/product-carousel.js';

const button = (attributes = {}, classes = []) => ({
  getAttribute: (name) => attributes[name] ?? null,
  classList: { contains: (name) => classes.includes(name) },
  closest: () => null,
});
const field = (name, value = '', type = 'text') => ({ name, value, type, disabled: false });
const form = ({ elements, classes = [], file = false, addButton = { value: '42' } }) => ({
  matches: (selector) => classes.some((name) => selector.includes(`.${name}`)),
  querySelector: (selector) => {
    if (selector.includes('type="file"')) return file ? {} : null;
    if (selector.includes('button[name="add-to-cart"]')) return addButton;
    if (selector.includes('input[name="add-to-cart"]')) return null;
    if (selector.includes('input[name="quantity"]')) return elements.find((el) => el.name === 'quantity') || null;
    return null;
  },
  elements,
});

test('a button is busy while aria-busy is set or WooCommerce has marked it loading', () => {
  assert.equal(isBusy(button({ 'aria-busy': 'true' })), true);
  assert.equal(isBusy(button({}, ['loading'])), true);
  assert.equal(isBusy(button()), false);
  assert.equal(isBusy(null), false);
});

test('the product name comes out of WooCommerce\'s label, and the message puts it in', () => {
  assert.equal(productName(button({ 'aria-label': 'Add “Large Grey Wall Clock” to your basket' })), 'Large Grey Wall Clock');
  assert.equal(addedMessage('%s added to basket.', 'Wall Clock'), 'Wall Clock added to basket.');
  assert.equal(addedMessage('', 'Clock'), 'Clock added to basket.');
});

test('only a plain quantity + button form is added without a reload', () => {
  const plain = form({ elements: [field('quantity', '2', 'number'), field('add-to-cart', '42', 'submit')] });

  assert.deepEqual(simpleProductPayload(plain), { id: '42', quantity: 2 });
  assert.equal(simpleProductPayload(form({ elements: [field('quantity', '1', 'number')], classes: ['variations_form'] })), null);
  assert.equal(simpleProductPayload(form({ elements: [field('quantity', '1', 'number'), field('engraving', 'x')] })), null, 'an add-on field means a normal submit');
  assert.equal(simpleProductPayload(form({ elements: [field('quantity', '1', 'number')], file: true })), null);
  assert.deepEqual(simpleProductPayload(form({ elements: [field('quantity', '-3', 'number')] })), { id: '42', quantity: 1 });
});

test('the carousel buttons know when everything fits and when a list is at either end', () => {
  assert.deepEqual(navState({ scrollLeft: 0, clientWidth: 1000, scrollWidth: 1000 }), { overflow: false, atStart: true, atEnd: true });
  assert.deepEqual(navState({ scrollLeft: 0, clientWidth: 1000, scrollWidth: 2400 }), { overflow: true, atStart: true, atEnd: false });
  assert.deepEqual(navState({ scrollLeft: 1400, clientWidth: 1000, scrollWidth: 2400 }), { overflow: true, atStart: false, atEnd: true });
  assert.equal(stepFor(1000), 900);
  assert.equal(stepFor(0), 1);
});
