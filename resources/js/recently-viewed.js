// Recently viewed products (app/woocommerce/recently-viewed.php, TPL-134). The page carries no visitor data: a product page has an empty marker
// (data-pkde-recent-view = its own id) and a placeholder box (data-pkde-recent). This file keeps the list in the browser's localStorage (first-party,
// no cookie), merges it into the account when the visitor is logged in, and fills the box with the next batch from the kit's endpoint.
// Storage that cannot be read or written (private mode, blocked) just means no list: nothing breaks.

export const KEY = 'pkde_recent';

/** Ids out of a stored value (pure): JSON array in, positive integers out, once each, at most $max. */
export function parseIds(raw, max = 20) {
  let list = [];

  try {
    list = JSON.parse(raw || '[]');
  } catch {
    list = [];
  }

  const ids = [];

  (Array.isArray(list) ? list : []).forEach((value) => {
    const id = Number(value);

    if (Number.isInteger(id) && id > 0 && !ids.includes(id)) ids.push(id);
  });

  return ids.slice(0, max);
}

/** The list after viewing a product (pure): it goes first, an earlier entry of it is dropped, the list is capped. */
export const recordView = (ids, id, max = 20) => parseIds(JSON.stringify([id, ...ids]), max);

function read(max) {
  try {
    return parseIds(window.localStorage.getItem(KEY), max);
  } catch {
    return [];
  }
}

function write(ids) {
  try {
    window.localStorage.setItem(KEY, JSON.stringify(ids));
  } catch {
    // storage is unavailable: the list lasts only for this page
  }
}

async function run() {
  const box = document.querySelector('[data-pkde-recent]');
  const view = document.querySelector('[data-pkde-recent-view]');
  const max = Number((box || view)?.dataset.max) || 20;
  let ids = read(max);
  const current = Number(view?.dataset.pkdeRecentView || 0);

  if (current) {
    ids = recordView(ids, current, max);
    write(ids);
  }

  // A logged-in visitor's list follows the account: one POST on a product page or where the box is, even with an empty browser list (a new device),
  // and the merged list comes back.
  const syncUrl = (box || view)?.dataset.syncUrl;

  if (document.body.classList.contains('logged-in') && syncUrl && (current || box)) {
    try {
      const response = await fetch(syncUrl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ ids: ids.join(',') }) });
      const data = await response.json();

      ids = parseIds(JSON.stringify(data.ids || ids), max);
      write(ids);
    } catch {
      // the browser's own list is still shown
    }
  }

  if (!box || !ids.length) return;

  const list = box.querySelector('[data-pkde-recent-list]');
  const more = box.querySelector('[data-pkde-recent-more]');
  const button = box.querySelector('[data-pkde-recent-button]');
  const status = box.querySelector('[data-pkde-recent-status]');
  const count = Number(box.dataset.count) || 5;
  let offset = 0;
  let busy = false;

  const load = async (announce) => {
    if (busy) return;

    busy = true;
    button?.setAttribute('aria-busy', 'true');

    try {
      const url = new URL(box.dataset.url, window.location.href);

      url.searchParams.set('ids', ids.join(','));
      url.searchParams.set('offset', String(offset));
      url.searchParams.set('count', String(count));
      if (current) url.searchParams.set('exclude', String(current));

      const data = await (await fetch(url, { credentials: 'same-origin' })).json();

      if (data.html) {
        const before = list.children.length;

        list.insertAdjacentHTML('beforeend', data.html);
        offset += data.shown;
        box.hidden = false;

        if (announce) {
          status.textContent = `${data.shown} more products shown.`;
          list.children[before]?.querySelector('a')?.focus();
        }
      }

      if (more) more.hidden = offset >= data.total;
    } catch {
      // the box stays as it is; the visitor can press the button again
    } finally {
      busy = false;
      button?.removeAttribute('aria-busy');
    }
  };

  button?.addEventListener('click', () => load(true));
  load(false);
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  run();
}
