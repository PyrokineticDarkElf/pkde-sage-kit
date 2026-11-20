import apiFetch from '@wordpress/api-fetch';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { useEffect, useRef, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

// The canvas shows the real Contact Form 7 form where a Shortcode block, or a Paragraph, holds only [contact-form-7 ...] (WP-100). The markup comes from the kit's
// /pkde/v1/form-preview route (app/form-preview.php): no scripts, no Turnstile, and the preview is inert, so nothing is submitted from the editor. Other shortcodes are
// untouched, and the block's own editing (the shortcode text) stays where it was, above the preview.
const CF7 = /^\s*\[contact-form-7(?:\s[^[\]]*)?\]\s*$/;

export const formShortcodeOf = (name, attributes) => {
  const text = name === 'core/shortcode' ? attributes.text : name === 'core/paragraph' ? String(attributes.content ?? '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ') : '';

  return typeof text === 'string' && CF7.test(text) ? text.trim() : '';
};

const answers = new Map(); // one request per shortcode and editor session

function FormPreview({ shortcode }) {
  const ref = useRef(null);
  const [html, setHtml] = useState(null);

  useEffect(() => {
    let live = true;
    if (!answers.has(shortcode)) answers.set(shortcode, apiFetch({ path: addQueryArgs('/pkde/v1/form-preview', { shortcode }) }).catch(() => ({ html: '', styles: [] })));
    answers.get(shortcode).then(({ html: markup, styles }) => {
      if (!live) return;
      const doc = ref.current?.ownerDocument;
      (styles ?? []).forEach((href) => {
        if (doc && !doc.querySelector(`link[href="${href}"]`)) {
          const link = doc.createElement('link');
          link.rel = 'stylesheet';
          link.href = href;
          doc.head.appendChild(link);
        }
      });
      setHtml(markup || '');
    });
    return () => { live = false; };
  }, [shortcode]);

  return (
    <div ref={ref} className="pkde-form-preview" contentEditable={false}>
      {html === null && <p className="pkde-form-preview__note">{__('Loading the form…', 'sage')}</p>}
      {html && (
        <>
          <div inert="" dangerouslySetInnerHTML={{ __html: html }} />
          <p className="pkde-form-preview__note">{__('Preview of the form. It cannot be submitted from the editor.', 'sage')}</p>
        </>
      )}
    </div>
  );
}

addFilter('editor.BlockEdit', 'pkde/form-preview', createHigherOrderComponent((BlockEdit) => (props) => {
  const shortcode = formShortcodeOf(props.name, props.attributes);

  return (
    <>
      <BlockEdit {...props} />
      {shortcode && <FormPreview shortcode={shortcode} />}
    </>
  );
}, 'withFormPreview'));
