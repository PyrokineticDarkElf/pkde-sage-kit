import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, ToggleControl, Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// Testimonials (sage/quotes). The page is rendered by the server (render.php), so the editor shows the same markup through ServerSideRender;
// the carousel's sliding is front-end JavaScript, so in the editor the slides sit side by side.
function Preview({ attributes }) {
  const ServerSideRender = window.wp?.serverSideRender;

  if (!ServerSideRender) {
    return <Placeholder icon="format-quote" label={__('Testimonials', 'sage')} />;
  }

  return (
    <ServerSideRender
      block={metadata.name}
      attributes={attributes}
      EmptyResponsePlaceholder={() => <Placeholder icon="format-quote" label={__('Testimonials', 'sage')}>{__('Nothing to show yet.', 'sage')}</Placeholder>}
    />
  );
}

function Edit({ attributes, setAttributes }) {
  const { eyebrow, heading, source, count, minRating, postType, layout, showStars, items } = attributes;
  const blockProps = useBlockProps();
  const setItem = (index, patch) => setAttributes({ items: items.map((item, at) => (at === index ? { ...item, ...patch } : item)) });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Heading', 'sage')}>
          <TextControl label={__('Small heading', 'sage')} value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
          <TextControl label={__('Heading', 'sage')} value={heading} onChange={(value) => setAttributes({ heading: value })} />
        </PanelBody>
        <PanelBody title={__('Layout', 'sage')}>
          <SelectControl
            label={__('Layout', 'sage')}
            value={layout}
            options={[{ value: 'carousel', label: __('Carousel', 'sage') }, { value: 'strip', label: __('Strip of three', 'sage') }, { value: 'panel', label: __('One large quote', 'sage') }]}
            onChange={(value) => setAttributes({ layout: value })}
          />
          <ToggleControl label={__('Show star ratings', 'sage')} checked={!!showStars} onChange={(value) => setAttributes({ showStars: value })} />
        </PanelBody>
        <PanelBody title={__('Where the quotes come from', 'sage')}>
          <SelectControl
            label={__('Source', 'sage')}
            value={source}
            options={[
              { value: 'reviews', label: __('WooCommerce product reviews', 'sage') },
              { value: 'posts', label: __('A testimonials post type', 'sage') },
              { value: 'manual', label: __('Typed in here', 'sage') },
            ]}
            onChange={(value) => setAttributes({ source: value })}
          />
          <RangeControl label={__('How many', 'sage')} min={1} max={24} value={count} onChange={(value) => setAttributes({ count: value })} />
          {source === 'reviews' && <RangeControl label={__('Lowest star rating shown', 'sage')} min={1} max={5} value={minRating} onChange={(value) => setAttributes({ minRating: value })} />}
          {source === 'posts' && <TextControl label={__('Post type (its name, e.g. testimonial)', 'sage')} value={postType} onChange={(value) => setAttributes({ postType: value })} />}
        </PanelBody>
        {source === 'manual' && (
          <PanelBody title={__('Quotes', 'sage')}>
            {items.map((item, index) => (
              <div key={index} style={{ marginBottom: '1.25rem', paddingBottom: '1rem', borderBottom: '1px solid #ddd' }}>
                <TextareaControl label={__('Quote', 'sage')} value={item.quote || ''} onChange={(value) => setItem(index, { quote: value })} />
                <TextControl label={__('Name', 'sage')} value={item.name || ''} onChange={(value) => setItem(index, { name: value })} />
                <TextControl label={__('Who they are (optional)', 'sage')} value={item.role || ''} onChange={(value) => setItem(index, { role: value })} />
                <RangeControl label={__('Stars (0 for none)', 'sage')} min={0} max={5} value={item.rating || 0} onChange={(value) => setItem(index, { rating: value })} />
                <Button variant="link" isDestructive onClick={() => setAttributes({ items: items.filter((_, at) => at !== index) })}>{__('Remove this quote', 'sage')}</Button>
              </div>
            ))}
            <Button variant="secondary" onClick={() => setAttributes({ items: [...items, { quote: '', name: '', role: '', rating: 5 }] })}>{__('Add a quote', 'sage')}</Button>
          </PanelBody>
        )}
      </InspectorControls>

      <div {...blockProps}>
        <Preview attributes={attributes} />
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
