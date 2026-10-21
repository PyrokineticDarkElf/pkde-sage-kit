/**
 * Shared click-to-enlarge lightbox for the Image Gallery and Image Carousel
 * blocks (gallery/render.php, carousel/render.php) — hand-rolled, not a
 * library, since a modal overlay + prev/next/close is simple enough not to
 * need one (unlike the carousel's own drag/loop/snap math — see
 * carousel.js's own comment for why that one uses Embla instead).
 *
 * Markup contract: any `[data-lightbox-trigger]` button, with
 * `data-lightbox-src` (full-size image URL), `data-lightbox-alt`, and
 * `data-lightbox-group` (an id shared by every trigger in the same
 * block instance, so Prev/Next cycles within that block's own images only —
 * see each render.php's own wp_unique_id() call). One overlay is built once
 * and reused for every group on the page.
 */
const triggers = Array.from(document.querySelectorAll('[data-lightbox-trigger]'));

if (triggers.length) {
  const overlay = document.createElement('div');
  overlay.className = 'fixed inset-0 z-[100] hidden items-center justify-center bg-neutral-950/90 p-4 sm:p-8';
  // Plain hardcoded English, not a client-side __() call — this is frontend
  // JS, where (unlike each render.php's own server-side __()) there's no
  // translation catalogue loaded to call at all.
  overlay.innerHTML = `
    <button type="button" data-lightbox-close aria-label="Close" class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
    </button>
    <button type="button" data-lightbox-prev aria-label="Previous image" class="absolute left-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-4">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 010 1.06L9.06 10l3.73 3.71a.75.75 0 11-1.06 1.06l-4.25-4.24a.75.75 0 010-1.06l4.25-4.24a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
    </button>
    <img data-lightbox-image class="max-h-full max-w-full rounded-lg object-contain" alt="" />
    <button type="button" data-lightbox-next aria-label="Next image" class="absolute right-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-4">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 010-1.06L10.94 10 7.21 6.29a.75.75 0 111.06-1.06l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" /></svg>
    </button>
  `;
  document.body.append(overlay);

  const image = overlay.querySelector('[data-lightbox-image]');
  const prevButton = overlay.querySelector('[data-lightbox-prev]');
  const nextButton = overlay.querySelector('[data-lightbox-next]');
  let activeGroup = [];
  let activeIndex = 0;
  let lastFocused = null;

  const show = (index) => {
    activeIndex = (index + activeGroup.length) % activeGroup.length;
    const trigger = activeGroup[activeIndex];
    image.src = trigger.dataset.lightboxSrc;
    image.alt = trigger.dataset.lightboxAlt || '';
  };

  const open = (trigger) => {
    const group = trigger.dataset.lightboxGroup;
    activeGroup = triggers.filter((candidate) => candidate.dataset.lightboxGroup === group);
    lastFocused = trigger;
    show(activeGroup.indexOf(trigger));
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    const hasMultiple = activeGroup.length > 1;
    prevButton.hidden = !hasMultiple;
    nextButton.hidden = !hasMultiple;
    document.body.style.overflow = 'hidden';
    overlay.querySelector('[data-lightbox-close]').focus();
  };

  const close = () => {
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    document.body.style.overflow = '';
    lastFocused?.focus();
  };

  triggers.forEach((trigger) => trigger.addEventListener('click', () => open(trigger)));
  overlay.querySelector('[data-lightbox-close]').addEventListener('click', close);
  prevButton.addEventListener('click', () => show(activeIndex - 1));
  nextButton.addEventListener('click', () => show(activeIndex + 1));
  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      close();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (overlay.classList.contains('hidden')) {
      return;
    }
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowLeft') show(activeIndex - 1);
    if (event.key === 'ArrowRight') show(activeIndex + 1);
  });
}
