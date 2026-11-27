import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { ServerPreview } from '../../js/server-preview.jsx';

function Edit({ attributes, setAttributes }) {
  const { heading, description, count } = attributes;
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
          <RangeControl
            label={__('Number of products', 'sage')}
            min={1}
            max={12}
            value={count}
            onChange={(value) => setAttributes({ count: value })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <ServerPreview block={metadata.name} attributes={attributes} icon="clock" label={__('Recent Products', 'sage')} empty={__('Displays the most recently added products (WooCommerce only). Configure the count in the block settings.', 'sage')} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
