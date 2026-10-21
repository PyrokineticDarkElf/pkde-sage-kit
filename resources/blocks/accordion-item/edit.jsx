import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// Kept deliberately narrow — a question/answer or accordion item is meant
// to stay simple. core/buttons lets an answer end with a "Learn more" link
// without reaching for a whole new block.
const ALLOWED_BLOCKS = ['core/paragraph', 'core/list', 'core/image', 'core/buttons'];
const TEMPLATE = [['core/paragraph', { placeholder: __('Write the answer…', 'sage') }]];

function Edit({ attributes, setAttributes }) {
  const { heading, open } = attributes;
  const blockProps = useBlockProps({ className: 'border-b border-neutral-200 py-4' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Settings', 'sage')}>
          <ToggleControl
            label={__('Open by default', 'sage')}
            checked={open}
            onChange={(value) => setAttributes({ open: value })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <RichText
          tagName="div"
          className="font-display text-lg font-semibold text-neutral-950"
          placeholder={__('Question or heading…', 'sage')}
          value={heading}
          onChange={(value) => setAttributes({ heading: value })}
          allowedFormats={[]}
        />
        <div className="prose-content mt-3">
          <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} />
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
