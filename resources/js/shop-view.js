// Grid / list view toggle (app/woocommerce/view-toggle.php): the choice lives in the visitor's browser, never in the HTML, so a cached page is the same
// for everyone. The head script has already set data-pkde-view before the first paint; this syncs the buttons and saves a click.
const KEY = 'pkde_shop_view';
const buttons = document.querySelectorAll('[data-pkde-view-set]');

if (buttons.length) {
  const read = () => {
    try {
      const saved = window.localStorage.getItem(KEY);

      return saved === 'list' ? 'list' : 'grid';
    } catch {
      return 'grid';
    }
  };
  const apply = (view) => {
    document.documentElement.dataset.pkdeView = view;
    buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.pkdeViewSet === view)));
  };

  apply(read());

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const view = button.dataset.pkdeViewSet === 'list' ? 'list' : 'grid';
      apply(view);

      try {
        window.localStorage.setItem(KEY, view);
      } catch {
        // private window or blocked storage: the view still changes for this page
      }
    });
  });
}
