import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';

// WordPress calls the space between blocks 'Block spacing'; it is the CSS gap, so the editor says Gap.
addFilter('i18n.gettext', 'sage/gap-label', (translation, text) => (text === 'Block spacing' ? 'Gap' : translation));

// Each custom block's editor UI is bundled here rather than loaded as a
// separate per-block asset (see app/blocks.php and should_load_separate_core_block_assets).
import '../blocks/section/edit.jsx';
import './animation.jsx';
import '../blocks/hero/edit.jsx';
import '../blocks/latest-posts/edit.jsx';
import '../blocks/entry-grid/edit.jsx';
import '../blocks/featured-posts/edit.jsx';
import '../blocks/product-categories/edit.jsx';
import '../blocks/shop-products/edit.jsx';
import '../blocks/product-loop/edit.jsx';
import '../blocks/brands/edit.jsx';
import '../blocks/recent-products/edit.jsx';
import '../blocks/best-selling-products/edit.jsx';
import '../blocks/featured-products/edit.jsx';
import '../blocks/cta/edit.jsx';
import '../blocks/accordion-item/edit.jsx';
import '../blocks/accordion/edit.jsx';
import '../blocks/faq/edit.jsx';
import '../blocks/gallery/edit.jsx';
import '../blocks/carousel/edit.jsx';
import '../blocks/hero-slide/edit.jsx';
import '../blocks/hero-slider/edit.jsx';

domReady(() => {
  // A link or form inside the editor canvas must never navigate it. Clicking a link in a Custom HTML block used to load that
  // front-end page inside the canvas, replacing what is being edited. The canvas is an iframe that WordPress creates and
  // re-creates (switching device preview, entering and leaving a block), so the guard is (re)attached on a timer.
  const guard = () => {
    const doc = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;

    if (!doc) return;

    // The front end wraps a page's content in .page-content (the front page also gets .is-front-page) and a site scopes its design
    // to them. The canvas root gets the same classes, so what is edited looks like what is printed.
    const editor = window.wp?.data?.select('core/editor');
    const frontPageId = editor?.getEditorSettings?.()?.frontPageId;
    doc.documentElement.classList.toggle('page-content', editor?.getCurrentPostType() === 'page');
    doc.documentElement.classList.toggle('is-front-page', !!frontPageId && frontPageId === editor?.getCurrentPostId());
    // A wide page template prints its text at the whole page measure: the canvas root says so, so editor-width.css can match it.
    const wideTemplates = editor?.getEditorSettings?.()?.pkdeWideTemplates ?? [];
    doc.documentElement.classList.toggle('is-wide-page', wideTemplates.includes(editor?.getEditedPostAttribute?.('template')));

    if (doc.canvasGuard) return;

    doc.canvasGuard = true;
    doc.addEventListener('click', (event) => {
      if (event.target.closest?.('a[href]')) event.preventDefault();
    }, true);
    doc.addEventListener('submit', (event) => event.preventDefault(), true);
  };

  guard();
  window.setInterval(guard, 1000);
});
