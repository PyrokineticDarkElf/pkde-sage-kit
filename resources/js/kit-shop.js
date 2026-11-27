// The shop's scripts, for a site that sells (WooCommerce is required): add to basket, the basket count, product carousels, recently viewed, the shop filters and the grid/list
// toggle. kit.js no longer carries any of it: a site with a shop imports this file after kit.js (the template's app.js has the line, commented out until bin/setup's shop path
// uncomments it).
import './add-to-basket.js';
import './cart-badge.js';
import './cart-count.js';
import './product-carousel.js';
import './recently-viewed.js';
import './shop-filters.js';
import './shop-view.js';

const filterToggle = document.querySelector('[data-filter-toggle]');

if (filterToggle) {
  const panel = document.getElementById(filterToggle.getAttribute('aria-controls'));

  if (panel) {
    filterToggle.addEventListener('click', () => {
      const expanded = filterToggle.getAttribute('aria-expanded') === 'true';

      filterToggle.setAttribute('aria-expanded', String(!expanded));
      panel.classList.toggle('hidden', expanded);
      // the recently viewed box sits outside the filters' landmark and follows the panel (partials/shop-archive-body)
      document.querySelectorAll('[data-filter-follow]').forEach((el) => el.classList.toggle('hidden', expanded));
    });
  }
}

document.querySelectorAll('[data-shop-filters]').forEach((form) => {
  form.addEventListener('submit', () => {
    form.querySelectorAll('[data-price-input]').forEach((input) => {
      if (input.value === '') input.removeAttribute('name');
    });
  });
});
