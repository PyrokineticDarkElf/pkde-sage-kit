// A WooCommerce review in the testimonials scrolls inside its card when it is longer than --pkde-clamp-quote-review lines (resources/css/clamp.css). A box
// that scrolls becomes keyboard focusable and named (the arrow keys then scroll it), and gets [data-pkde-more] while there is more below (the soft fade at the bottom
// edge). A box whose text fits is left exactly as it is: no tabindex, no role, no fade.
const boxes = document.querySelectorAll('.pkde-quotes--reviews .pkde-quote blockquote');

const sync = (box) => {
  const scrolls = box.scrollHeight > box.clientHeight + 1;

  if (scrolls) {
    box.tabIndex = 0;
    box.setAttribute('role', 'region');
    box.setAttribute('aria-label', box.dataset.pkdeLabel || 'Review');
  } else {
    box.removeAttribute('tabindex');
    box.removeAttribute('role');
    box.removeAttribute('aria-label');
  }

  box.toggleAttribute('data-pkde-more', scrolls && box.scrollTop + box.clientHeight < box.scrollHeight - 1);
};

boxes.forEach((box) => {
  sync(box);
  box.addEventListener('scroll', () => sync(box), { passive: true });

  if ('ResizeObserver' in window) {
    new ResizeObserver(() => sync(box)).observe(box);
  }
});
