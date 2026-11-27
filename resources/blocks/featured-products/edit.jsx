import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { ServerPreview } from '../../js/server-preview.jsx';

function Edit({ attributes, setAttributes }) {
  const { heading, description, source, count, skus } = attributes;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section heading', 'sage')}>
          <TextControl
            label={__('Heading', 'sage')}
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
          />
          <TextareaControl
            label={__('Description', 'sage')}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
          />
        </PanelBody>
        <PanelBody title={__('Settings', 'sage')}>
          <SelectControl
            label={__('Source', 'sage')}
            value={source}
            options={[
              { label: __('Products marked "Featured"', 'sage'), value: 'featured' },
              { label: __('Manually chosen (by SKU)', 'sage'), value: 'manual' },
            ]}
            onChange={(value) => setAttributes({ source: value })}
          />

          {source === 'featured' && (
            <RangeControl
              label={__('Number of products', 'sage')}
              min={1}
              max={12}
              value={count}
              onChange={(value) => setAttributes({ count: value })}
            />
          )}

          {source === 'manual' && (
            <TextControl
              label={__('SKUs', 'sage')}
              help={__('Comma-separated SKUs, in the order you want them shown.', 'sage')}
              value={skus}
              onChange={(value) => setAttributes({ skus: value })}
            />
          )}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <ServerPreview block={metadata.name} attributes={attributes} icon="star-filled" label={__('Featured Products', 'sage')} empty={__('Displays featured or manually chosen products (WooCommerce only). Configure the source in the block settings.', 'sage')} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
