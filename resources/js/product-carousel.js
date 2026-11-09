// The product page's "more products" sections (partials/product-section, MGR-79): previous / next buttons for a CSS scroll-snap list, and the
// optional "Show more" on the related section. No library: the list scrolls by itself (touch, wheel, keyboard, the browser's own snap), this file
// only adds the buttons and keeps them honest (hidden when everything fits, aria-disabled at either end). Reduced motion jumps instead of gliding.

/** How far one button press moves: the visible width less a little, so the next card is partly in view. */
export const stepFor = (clientWidth) => Math.max(1, Math.round(clientWidth * 0.9));

/** What the buttons should show for a scroll position (pure): { overflow, atStart, atEnd }. */
export function navState({ scrollLeft, clientWidth, scrollWidth }) {
  const overflow = scrollWidth - clientWidth > 1;

  return { overflow, atStart: scrollLeft <= 1, atEnd: scrollLeft + clientWidth >= scrollWidth - 1 };
}

/** The product ids already in a list, from WooCommerce's `post-ID` class on the cards. */
export const shownIds = (track) => [...track.querySelectorAll('li.product')]
  .map((card) => (card.className.match(/(?:^|\s)post-(\d+)(?:\s|$)/) || [])[1])
  .filter(Boolean);

function setup(root) {
  const track = root.querySelector('[data-pkde-carousel-track]');
  const nav = root.querySelector('[data-pkde-carousel-nav]');
  const prev = root.querySelector('[data-pkde-carousel-prev]');
  const next = root.querySelector('[data-pkde-carousel-next]');

  if (!track) return;

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const disable = (button, off) => {
    button.setAttribute('aria-disabled', String(off));
    button.toggleAttribute('data-disabled', off);
  };
  const update = () => {
    const state = navState(track);

    if (nav) nav.hidden = !state.overflow;
    if (prev && next) {
      disable(prev, state.atStart);
      disable(next, state.atEnd);
    }
  };
  const move = (direction) => track.scrollBy({ left: direction * stepFor(track.clientWidth), behavior: reduced.matches ? 'auto' : 'smooth' });

  prev?.addEventListener('click', () => { if (prev.getAttribute('aria-disabled') !== 'true') move(-1); });
  next?.addEventListener('click', () => { if (next.getAttribute('aria-disabled') !== 'true') move(1); });
  track.addEventListener('scroll', update, { passive: true }); // scroll events already arrive once per frame
  window.addEventListener('resize', update);
  if (window.ResizeObserver) new ResizeObserver(update).observe(track);
  update();

  // "Show more" (related only): the next products as the site's own cards, from the kit's WooCommerce endpoint.
  const more = root.querySelector('[data-pkde-load-more]');

  more?.addEventListener('click', async () => {
    if (more.getAttribute('aria-busy') === 'true') return;

    more.setAttribute('aria-busy', 'true');

    try {
      const url = new URL(more.dataset.pkdeLoadMore, window.location.href);

      url.searchParams.set('exclude', shownIds(track).join(','));

      const data = await (await fetch(url, { credentials: 'same-origin' })).json();

      if (data.html) {
        const before = track.children.length;

        track.insertAdjacentHTML('beforeend', data.html);
        // the first new card is where the visitor goes next: bring it into view
        track.children[before]?.scrollIntoView({ behavior: reduced.matches ? 'auto' : 'smooth', block: 'nearest', inline: 'start' });
      }
      if (data.done || !data.html) more.closest('p')?.remove();
    } catch {
      // the button stays: the visitor can try again
    } finally {
      more.removeAttribute('aria-busy');
      update();
    }
  });
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') {
  document.querySelectorAll('[data-pkde-carousel]').forEach(setup);
}
