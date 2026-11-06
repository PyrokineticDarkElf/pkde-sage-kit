// The header's basket count is filled in by the browser (resources/js/cart-badge.js, MGR-76). The module runs without a DOM here: every function takes
// the document, fetch and cookie it needs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { clearCount, fragmentsUrl, hasBasketCookie, syncBadge } from '../../resources/js/cart-badge.js';

const badge = (html = '') => ({ outerHTML: html, innerHTML: html });
const documentWith = (counts, link) => ({
  querySelectorAll: () => counts,
  querySelector: () => (link ? { closest: () => ({ href: link }) } : null),
});
const jsonResponse = (fragments) => async () => ({ json: async () => ({ fragments }) });

test('reads the basket cookie WooCommerce sets while the basket has items', () => {
  assert.equal(hasBasketCookie('a=1; woocommerce_items_in_cart=1; b=2'), true);
  assert.equal(hasBasketCookie('woocommerce_items_in_cart=1'), true);
  assert.equal(hasBasketCookie('xwoocommerce_items_in_cart=1; wp_woocommerce_session_abc=1'), false);
  assert.equal(hasBasketCookie(''), false);
});

test('asks for the fragments next to the basket link, else at the site root', () => {
  assert.equal(fragmentsUrl('https://shop.test/basket/', 'https://shop.test'), 'https://shop.test/basket/?wc-ajax=get_refreshed_fragments');
  assert.equal(fragmentsUrl('https://shop.test/sub/basket/', 'https://shop.test'), 'https://shop.test/sub/basket/?wc-ajax=get_refreshed_fragments');
  assert.equal(fragmentsUrl(undefined, 'https://shop.test'), 'https://shop.test/?wc-ajax=get_refreshed_fragments');
});

test('a visitor with a basket cookie gets their own count swapped in, by a POST that carries their cookies', async () => {
  const counts = [badge(), badge()];
  let call;
  const fetchFn = async (url, options) => {
    call = { url, options };

    return { json: async () => ({ fragments: { 'span.pkde-cart-count': '<span class="pkde-cart-count">3</span>' } }) };
  };

  const result = await syncBadge({ doc: documentWith(counts, 'https://shop.test/basket/'), cookie: 'woocommerce_items_in_cart=1', fetchFn, origin: 'https://shop.test' });

  assert.equal(result, 'refreshed');
  assert.equal(call.options.method, 'POST');
  assert.equal(call.options.credentials, 'same-origin');
  assert.match(call.url, /wc-ajax=get_refreshed_fragments/);
  assert.deepEqual(counts.map((c) => c.outerHTML), ['<span class="pkde-cart-count">3</span>', '<span class="pkde-cart-count">3</span>']);
});

test('a visitor without the cookie makes no request and sees no count, whatever the cached page carried', async () => {
  const counts = [badge('<span>7</span>')];
  const fetchFn = async () => assert.fail('no request without the basket cookie');

  assert.equal(await syncBadge({ doc: documentWith(counts), cookie: 'other=1', fetchFn, origin: 'https://shop.test' }), 'cleared');
  assert.equal(counts[0].innerHTML, '');
});

test('a failed or empty answer leaves the count alone and never throws', async () => {
  const counts = [badge()];
  const doc = documentWith(counts, 'https://shop.test/basket/');
  const cookie = 'woocommerce_items_in_cart=1';

  assert.equal(await syncBadge({ doc, cookie, fetchFn: async () => { throw new Error('offline'); }, origin: 'https://shop.test' }), 'failed');
  assert.equal(await syncBadge({ doc, cookie, fetchFn: jsonResponse({}), origin: 'https://shop.test' }), 'failed');
  assert.equal(counts[0].outerHTML, '');
  assert.equal(await syncBadge({ doc: documentWith([]), cookie, fetchFn: jsonResponse({}), origin: 'https://shop.test' }), 'none');
});

test('clearCount empties every badge', () => {
  const counts = [badge('<span>2</span>'), badge('<span>2</span>')];

  clearCount({ querySelectorAll: () => counts });

  assert.deepEqual(counts.map((c) => c.innerHTML), ['', '']);
});
