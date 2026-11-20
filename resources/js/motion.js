// Reveal on scroll (WP-103): a block the server marked `pkde-reveal` fades and rises a few pixels once, when it first comes into view. The hidden state is CSS and applies only
// while this script has put `js-motion` on <html> (motion.css), so without JavaScript, without IntersectionObserver, with the site switch off or for a visitor who prefers reduced
// motion nothing is ever hidden. Anything already on screen when the page loads is shown at once (it is above the fold: it never waits, never animates), anything above the
// viewport (a jump to an anchor, a restored scroll position) is shown too, and at the bottom of the page everything left is shown, so a short last block is never stuck.
const root = document.documentElement;
const items = [...document.querySelectorAll('.pkde-reveal')];

if (items.length && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && !/\bpkde-motion-(off|reduced)\b/.test(root.className)) {
  const show = (item) => item.classList.add('is-revealed');
  const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
    if (entry.isIntersecting || entry.boundingClientRect.top < 0) {
      show(entry.target);
      observer.unobserve(entry.target);
    }
  }));

  // first show what is on screen or behind us, THEN switch the hidden state on: nothing visible is ever hidden, so no flash and no layout change
  items.forEach((item) => {
    const box = item.getBoundingClientRect();
    if (box.top < window.innerHeight) show(item);
    else observer.observe(item);
  });
  root.classList.add('js-motion');

  window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= root.scrollHeight - 2) items.forEach(show);
  }, { passive: true });
}
