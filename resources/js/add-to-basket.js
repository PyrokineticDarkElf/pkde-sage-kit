// Add-to-basket feedback (MGR-79). Every add-to-basket button (the product cards' AJAX buttons and the product page's form button) gets a busy state
// while it works: aria-busy, a spinner (resources/css/add-to-basket.css), no second click; then an "Added" state, WooCommerce's "View basket" link
// and a polite live message. The words come from the region app/woocommerce/add-to-basket.php prints (same for every visitor, so the page cache is
// unaffected).
//
//   - AJAX buttons (cards): WooCommerce's own add-to-cart script fires `adding_to_cart` and `added_to_cart` on the body (jQuery events); we listen.
//   - The product page's form: `submit` marks the button busy. For a simple product (the plain quantity + button form) the kit adds it with one
//     request to WooCommerce's `add_to_cart` endpoint and shows "Added" without a reload; any failure submits the form the ordinary way, so nothing
//     is lost. Variable, grouped and add-on forms always submit normally.
//   - The button is never `disabled` while busy: a disabled submit button is left out of the form data and the product id (the button's value)
//     would be lost. It is aria-disabled and a capturing click handler swallows the second click.

const ADDED_MS = 3000; // how long the button says "Added"
const WATCHDOG_MS = 20000; // a busy button nothing answered for is released

const ALLOWED_FIELDS = new Set(['quantity', 'add-to-cart', '_wp_http_referer']);

/** True while a button is working (ours or WooCommerce's own `loading` class). */
export const isBusy = (button) => !!button && (button.getAttribute('aria-busy') === 'true' || button.classList.contains('loading'));

/** The product name out of WooCommerce's label ("Add “Name” to your basket"), else the card's title, else the page's. */
export function productName(button) {
  const label = button.getAttribute('aria-label') || '';
  const quoted = label.match(/[“"‘'](.+)[”"’']/);

  if (quoted) return quoted[1];

  const title = button.closest('li.product, .product')?.querySelector('.woocommerce-loop-product__title, .product-card__title, .product_title, h1, h2');

  return (title?.textContent || '').trim();
}

/** "%s added to basket." with the name put in. */
export const addedMessage = (template, name) => (template || '%s added to basket.').replace('%s', name || '');

/**
 * The product id and quantity of a plain simple-product form, or null when the form is anything else (variations, grouped products, extra fields from
 * an add-on plugin, a file input): only those are added without a reload.
 */
export function simpleProductPayload(form) {
  if (!form || form.matches('.variations_form, .grouped_form, .bundle_form') || form.querySelector('input[type="file"]')) return null;

  const button = form.querySelector('button[name="add-to-cart"], input[name="add-to-cart"]');
  const id = button?.value || form.querySelector('input[name="add-to-cart"]')?.value;
  const named = [...form.elements].filter((el) => el.name && !el.disabled && el.type !== 'submit' && el.type !== 'button');

  if (!id || named.some((el) => !ALLOWED_FIELDS.has(el.name))) return null;

  const quantity = Number(form.querySelector('input[name="quantity"]')?.value || 1);

  return { id, quantity: Number.isFinite(quantity) && quantity > 0 ? quantity : 1 };
}

/** Replaces the page's fragments (the header count ...) with WooCommerce's fresh ones: { selector: html }. */
export function applyFragments(doc, fragments) {
  Object.entries(fragments || {}).forEach(([selector, html]) => {
    try {
      doc.querySelectorAll(selector).forEach((el) => { el.outerHTML = html; });
    } catch {
      // a selector this browser does not accept: leave it, the next page load refreshes the count
    }
  });
}

export function setBusy(button, on) {
  if (on) {
    button.setAttribute('aria-busy', 'true');
    button.setAttribute('aria-disabled', 'true');
  } else {
    button.removeAttribute('aria-busy');
    button.removeAttribute('aria-disabled');
  }
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  const region = () => document.querySelector('[data-pkde-atc-status]');
  const word = (name, fallback = '') => region()?.dataset[name] || fallback;
  const timers = new WeakMap();
  const labels = new WeakMap();

  const say = (text) => {
    const live = region();

    if (!live) return;

    live.textContent = '';
    // a second add of the same product must be announced again: change the text after a tick
    window.setTimeout(() => { live.textContent = text; }, 30);
  };

  const release = (button) => {
    window.clearTimeout(timers.get(button));
    setBusy(button, false);
  };

  const markBusy = (button) => {
    if (!button) return;

    button.removeAttribute('data-pkde-added');
    setBusy(button, true);
    say(word('adding', 'Adding to basket…'));
    window.clearTimeout(timers.get(button));
    timers.set(button, window.setTimeout(() => { release(button); say(word('error')); }, WATCHDOG_MS));
  };

  const markAdded = (button) => {
    if (!button) return;

    release(button);
    button.setAttribute('data-pkde-added', '');
    say(addedMessage(word('added'), productName(button)));

    // The label reads "Added" for a moment when the button is plain text (an icon button keeps its icon; the check mark is CSS).
    if (!button.children.length && !labels.has(button)) {
      labels.set(button, button.textContent);
      button.textContent = word('addedLabel', 'Added');
      window.setTimeout(() => {
        button.textContent = labels.get(button);
        labels.delete(button);
        button.removeAttribute('data-pkde-added');
      }, ADDED_MS);
    }
  };

  // Second clicks: swallowed before WooCommerce's handler (document, capture) while a button is busy.
  document.addEventListener('click', (event) => {
    const button = event.target.closest?.('.add_to_cart_button, .single_add_to_cart_button');

    if (button && isBusy(button)) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);

  // The cards' AJAX buttons: WooCommerce's events.
  const $ = window.jQuery;

  if ($) {
    $(document.body).on('adding_to_cart', (event, $button) => markBusy($button?.[0]));
    $(document.body).on('added_to_cart', (event, fragments, hash, $button) => markAdded($button?.[0]));
  }

  // The product page's form.
  const ajaxUrl = () => word('ajaxUrl') || region()?.getAttribute('data-ajax-url') || '';

  document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!form.matches?.('form.cart') || form.dataset.pkdeBypass) return;

    const button = event.submitter?.matches?.('.single_add_to_cart_button') ? event.submitter : form.querySelector('.single_add_to_cart_button');

    if (!button) return;

    if (isBusy(button)) {
      event.preventDefault();

      return;
    }

    const payload = region()?.dataset.ajaxSingle === '1' && ajaxUrl() ? simpleProductPayload(form) : null;

    markBusy(button);

    if (!payload) return; // an ordinary submit: the page reloads; the busy state shows until it does

    event.preventDefault();

    const fallback = () => {
      release(button);
      form.dataset.pkdeBypass = '1';
      form.requestSubmit ? form.requestSubmit(button) : form.submit();
    };

    try {
      const controller = new AbortController();
      const timeout = window.setTimeout(() => controller.abort(), WATCHDOG_MS - 2000);
      const response = await fetch(ajaxUrl(), {
        method: 'POST',
        credentials: 'same-origin',
        signal: controller.signal,
        body: new URLSearchParams({ product_id: payload.id, quantity: String(payload.quantity) }),
      });

      window.clearTimeout(timeout);

      const data = await response.json();

      if (!data || data.error || !data.fragments) return fallback();

      applyFragments(document, data.fragments);
      if ($) $(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, $(button)]);
      else markAdded(button);

      // WooCommerce's own link after the button, as the cards get it.
      if (!button.parentElement.querySelector('.added_to_cart')) {
        const view = document.createElement('a');

        view.href = word('viewUrl') || region()?.getAttribute('data-view-url') || '/';
        view.className = 'added_to_cart wc-forward';
        view.textContent = word('viewLabel', 'View basket');
        button.after(view);
      }
    } catch {
      fallback();
    }
  });

  // A page restored from the back button must not show a button stuck busy.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) document.querySelectorAll('[aria-busy="true"]').forEach((button) => setBusy(button, false));
  });
}
