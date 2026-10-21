import './carousel.js';
import './lightbox.js';

const toc = document.querySelector('[data-toc]');

if (toc) {
  const links = new Map();

  toc.querySelectorAll('[data-toc-link]').forEach((link) => {
    links.set(link.getAttribute('data-toc-link'), link);
  });

  const headings = [...links.keys()]
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  if (headings.length) {
    const setActive = (id) => {
      links.forEach((link, linkId) => {
        if (linkId === id) {
          link.setAttribute('data-active', 'true');
        } else {
          link.removeAttribute('data-active');
        }
      });
    };

    const observer = new IntersectionObserver(
      (entries) => {
        // The heading closest to the top of the "active" band wins — avoids
        // flicker between adjacent sections when several are onscreen at once.
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

        if (visible[0]) {
          setActive(visible[0].target.id);
        }
      },
      {
        rootMargin: '-96px 0px -70% 0px',
        threshold: 0,
      }
    );

    headings.forEach((heading) => observer.observe(heading));
  }
}

document.querySelectorAll('[data-heading-copy]').forEach((button) => {
  const originalLabel = button.getAttribute('aria-label') || 'Copy link';

  button.addEventListener('click', async () => {
    const heading = button.closest('h1, h2, h3, h4, h5, h6');

    if (!heading?.id) return;

    const url = new URL(window.location.href);
    url.hash = heading.id;

    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(url.href);
      } else {
        const fallback = document.createElement('textarea');
        fallback.value = url.href;
        fallback.style.position = 'fixed';
        fallback.style.opacity = '0';
        document.body.append(fallback);
        fallback.select();
        const copied = document.execCommand('copy');
        fallback.remove();
        if (!copied) throw new Error('Clipboard copy failed');
      }

      button.dataset.copied = 'true';
      button.setAttribute('aria-label', 'Link copied');
      window.setTimeout(() => {
        delete button.dataset.copied;
        button.setAttribute('aria-label', originalLabel);
      }, 1800);
    } catch {
      // Clipboard access is unavailable in some non-secure contexts. The
      // section remains directly linkable through the visible ToC instead.
    }
  });
});

const navToggle = document.querySelector('[data-nav-toggle]');

if (navToggle) {
  const panel = document.getElementById(navToggle.getAttribute('aria-controls'));

  if (panel) {
    navToggle.addEventListener('click', () => {
      const expanded = navToggle.getAttribute('aria-expanded') === 'true';

      navToggle.setAttribute('aria-expanded', String(!expanded));
      panel.classList.toggle('hidden', expanded);
    });

    // Escape closes the open menu and puts focus back on its button.
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
        navToggle.setAttribute('aria-expanded', 'false');
        panel.classList.add('hidden');
        navToggle.focus();
      }
    });
  }
}

const filterToggle = document.querySelector('[data-filter-toggle]');

if (filterToggle) {
  const panel = document.getElementById(filterToggle.getAttribute('aria-controls'));

  if (panel) {
    filterToggle.addEventListener('click', () => {
      const expanded = filterToggle.getAttribute('aria-expanded') === 'true';

      filterToggle.setAttribute('aria-expanded', String(!expanded));
      panel.classList.toggle('hidden', expanded);
    });
  }
}

// Generic modal. Any [data-modal-trigger="id"] opens the element #id; a [data-modal-close] inside it, a click on the backdrop
// (the #id element itself) or Escape closes it. Focus moves into the dialog when it opens and returns to the trigger when it
// closes, and the page behind does not scroll. The markup is the site's: give the #id element role="dialog", aria-modal="true"
// and an accessible name (aria-label or aria-labelledby), the classes `hidden fixed inset-0 z-50 items-center justify-center`
// and a [data-modal-close] button. Both "hidden" and "flex" are toggled together: a modal needs display:flex to centre.
document.querySelectorAll('[data-modal-trigger]').forEach((trigger) => {
  const modal = document.getElementById(trigger.getAttribute('data-modal-trigger'));

  if (!modal) return;

  const openModal = () => {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    (modal.querySelector('[autofocus], input, select, textarea, a[href], button:not([data-modal-close])') ?? modal.querySelector('[data-modal-close]') ?? modal).focus?.();
  };

  const closeModal = () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    trigger.focus();
  };

  trigger.addEventListener('click', openModal);
  modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
  modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
  });
});

document.querySelectorAll('[data-shop-filters]').forEach((form) => {
  form.addEventListener('submit', () => {
    form.querySelectorAll('[data-price-input]').forEach((input) => {
      if (input.value === '') input.removeAttribute('name');
    });
  });

  form.querySelectorAll('input').forEach((input) => {
    input.addEventListener('change', () => form.requestSubmit());
  });
});
