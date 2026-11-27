import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';
import { ServerPreview } from '../../js/server-preview.jsx';

function Edit({ attributes, setAttributes }) {
  const { heading, description, source, count, postIds } = attributes;
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
              { label: __('Posts stuck to the front page', 'sage'), value: 'sticky' },
              { label: __('Manually chosen', 'sage'), value: 'manual' },
            ]}
            onChange={(value) => setAttributes({ source: value })}
          />

          {source === 'sticky' && (
            <RangeControl
              label={__('Number of posts', 'sage')}
              min={1}
              max={12}
              value={count}
              onChange={(value) => setAttributes({ count: value })}
            />
          )}

          {source === 'manual' && (
            <TextControl
              label={__('Post IDs', 'sage')}
              help={__('Comma-separated post IDs, in the order you want them shown.', 'sage')}
              value={postIds}
              onChange={(value) => setAttributes({ postIds: value })}
            />
          )}
        </PanelBody>
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <ServerPreview block={metadata.name} attributes={attributes} icon="star-filled" label={__('Featured Posts', 'sage')} empty={__('Displays sticky or manually chosen posts. Configure the source in the block settings.', 'sage')} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
