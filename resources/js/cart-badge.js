// The header's basket count, filled in by the browser (MGR-76). The page always arrives with an EMPTY count (app/woocommerce/cart-count.php), the same
// for every visitor, so the page cache can serve a visitor who has a basket. WooCommerce sets the cookie `woocommerce_items_in_cart` while the basket has
// items; when it is there we ask WooCommerce for the fresh cart fragments (`?wc-ajax=get_refreshed_fragments`, a POST that is never cached) and swap the
// count in; when it is not, the count is cleared (a basket emptied in another tab, a page restored from the back button).
const COOKIE = /(?:^|;\s*)woocommerce_items_in_cart=/;
const FRAGMENT = 'span.pkde-cart-count';

export const hasBasketCookie = (cookie) => COOKIE.test(cookie || '');

/** The fragments endpoint, next to the basket link (so a site in a sub-folder works), else at the site root. */
export const fragmentsUrl = (cartHref, origin) => new URL('?wc-ajax=get_refreshed_fragments', cartHref || `${origin}/`).toString();

/** Swaps WooCommerce's count element into every badge; resolves true when it did. */
export async function refreshCount({ doc, fetchFn, url }) {
  const response = await fetchFn(url, { method: 'POST', credentials: 'same-origin' });
  const html = (await response.json())?.fragments?.[FRAGMENT];

  if (!html) return false;

  doc.querySelectorAll('.pkde-cart-count').forEach((count) => { count.outerHTML = html; });

  return true;
}

/** Empties every count (and so hides the badge). */
export function clearCount(doc) {
  doc.querySelectorAll('.pkde-cart-count').forEach((count) => { count.innerHTML = ''; });
}

/** Brings the badge in line with the visitor's basket: 'none' (no badge on the page), 'refreshed', 'cleared' or 'failed'. */
export async function syncBadge({ doc, cookie, fetchFn, origin }) {
  if (!doc.querySelectorAll('.pkde-cart-count').length) return 'none';

  if (!hasBasketCookie(cookie)) {
    clearCount(doc);

    return 'cleared';
  }

  try {
    const link = doc.querySelector('[data-pkde-cart-badge]')?.closest?.('a');

    return (await refreshCount({ doc, fetchFn, url: fragmentsUrl(link?.href, origin) })) ? 'refreshed' : 'failed';
  } catch {
    return 'failed'; // the count stays empty until the next page; the basket link still works
  }
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  const run = () => syncBadge({ doc: document, cookie: document.cookie, fetchFn: window.fetch.bind(window), origin: window.location.origin });

  run();
  window.addEventListener('pageshow', (event) => { if (event.persisted) run(); });
}
