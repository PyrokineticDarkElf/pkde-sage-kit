import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

/**
 * Drives the Image Carousel and Hero Slider blocks (data-carousel/-viewport/
 * -track/-prev/-next/-dot, set by their own render.php files) using Embla —
 * https://www.embla-carousel.com. This replaced an earlier hand-rolled
 * CSS-scroll-snap version: that one had no real infinite loop (only
 * scrollIntoView to the nearest/first/last slide), imprecise jump distances
 * at partial-width slide sizes, and no drag beyond whatever the browser's
 * native scroll gave for free. Embla is a small (~6kb), dependency-free
 * library built specifically for this — loop, precise snap distance, and
 * pointer/touch drag all come from its own core, not a plugin, so this file
 * is now just wiring: attach Embla to each track, then connect the
 * server-rendered arrow buttons and pagination dots to its API.
 */
document.querySelectorAll('[data-carousel]').forEach((root) => {
  const viewport = root.querySelector('[data-carousel-viewport]');
  const track = root.querySelector('[data-carousel-track]');
  const slideCount = track ? track.children.length : 0;

  if (!viewport || !track || slideCount < 2) {
    return;
  }

  const loop = root.dataset.carouselLoop === 'true';

  // Autoplay is off unless the block turns it on, and never runs for a visitor who prefers reduced motion (WCAG 2.2.2 and
  // 2.3.3). It pauses while the pointer is over the slider or focus is inside it; a Pause button (rendered with autoplay)
  // stops and restarts it; and it stops while the tab is hidden. Dragging or clicking a dot does not cancel it for good.
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const wantsAutoplay = root.dataset.carouselAutoplay === 'true' && !prefersReducedMotion;
  const autoplayDelay = Number(root.dataset.carouselAutoplayDelay) || 5000;
  const autoplay = wantsAutoplay
    ? Autoplay({ delay: autoplayDelay, stopOnInteraction: false, stopOnMouseEnter: true, stopOnFocusIn: true })
    : null;

  // `data-carousel-duration`: how long a move takes (Embla's duration, 25 by default; a larger number is slower)
  const duration = Number(root.dataset.carouselDuration) || 25;

  root.setAttribute('data-carousel-ready', ''); // CSS: without script the row simply scrolls sideways
  const emblaApi = EmblaCarousel(viewport, { loop, align: 'start', duration }, autoplay ? [autoplay] : []);

  const toggle = root.querySelector('[data-carousel-autoplay-toggle]');

  if (autoplay) {
    let userPaused = false;

    const syncToggle = () => {
      if (!toggle) return;
      toggle.setAttribute('aria-label', userPaused ? toggle.dataset.labelPlay : toggle.dataset.labelPause);
      toggle.setAttribute('aria-pressed', String(userPaused));
      toggle.toggleAttribute('data-paused', userPaused);
    };

    toggle?.addEventListener('click', () => {
      userPaused = !userPaused;
      userPaused ? autoplay.stop() : autoplay.play();
      syncToggle();
    });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) autoplay.stop();
      else if (!userPaused) autoplay.play();
    });

    syncToggle();
  } else {
    // No autoplay (off, or reduced motion): the Pause button has nothing to control.
    toggle?.remove();
  }

  const prevButton = root.querySelector('[data-carousel-prev]');
  const nextButton = root.querySelector('[data-carousel-next]');
  // An empty [data-carousel-dots] holder gets one dot per scroll position, made here, because how many positions there are depends on how many cards fit
  const dotHolder = root.querySelector('[data-carousel-dots]');
  const makeDots = () => {
    if (!dotHolder) return;
    dotHolder.hidden = emblaApi.scrollSnapList().length < 2; // everything fits: nothing to move to
    dotHolder.replaceChildren(...emblaApi.scrollSnapList().map((_, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('data-carousel-dot', '');
      dot.setAttribute('aria-label', `${dotHolder.dataset.label || 'Go to position'} ${index + 1}`);
      dot.innerHTML = '<span class="pkde-dot"></span>';
      return dot;
    }));
  };
  makeDots();
  const dots = Array.from(root.querySelectorAll('[data-carousel-dot]'));

  // A visitor who asked for less motion gets the next slide at once, not a slide: the second argument is Embla's "jump".
  prevButton?.addEventListener('click', () => emblaApi.scrollPrev(prefersReducedMotion));
  nextButton?.addEventListener('click', () => emblaApi.scrollNext(prefersReducedMotion));
  dots.forEach((dot, index) => dot.addEventListener('click', () => emblaApi.scrollTo(index, prefersReducedMotion)));

  if (dots.length) {
    const setActiveDot = () => {
      const selected = emblaApi.selectedScrollSnap();
      dots.forEach((dot, index) => dot.toggleAttribute('data-active', index === selected));
    };

    emblaApi.on('select', setActiveDot);
    setActiveDot();

    if (dotHolder) {
      // a resize can change how many positions there are: make the dots again
      emblaApi.on('reInit', () => {
        makeDots();
        const fresh = Array.from(root.querySelectorAll('[data-carousel-dot]'));
        fresh.forEach((dot, index) => dot.addEventListener('click', () => emblaApi.scrollTo(index, prefersReducedMotion)));
        dots.length = 0;
        dots.push(...fresh);
        setActiveDot();
      });
    }
  }
});
