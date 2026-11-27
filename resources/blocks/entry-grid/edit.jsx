import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';
import { ServerPreview } from '../../js/server-preview.jsx';

function Edit({ attributes, setAttributes }) {
  const { heading, description, postType, count } = attributes;
  const blockProps = useBlockProps();
  const showAll = count === 0;

  // Every public, UI-visible post type ('post' plus any registered CPT); 'attachment' is excluded as a sensible loop source.
  const postTypes = useSelect(
    (select) => (select('core').getPostTypes({ per_page: -1 }) ?? []).filter(
      (type) => type.viewable && type.slug !== 'attachment'
    ),
    []
  );

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section heading', 'sage')}>
          <TextControl
            label={__('Heading (optional)', 'sage')}
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
          />
          <TextareaControl
            label={__('Description (optional)', 'sage')}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
          />
        </PanelBody>
        <PanelBody title={__('Settings', 'sage')}>
          <SelectControl
            label={__('Post type', 'sage')}
            value={postType}
            options={postTypes.map((type) => ({ label: type.name, value: type.slug }))}
            onChange={(value) => setAttributes({ postType: value })}
            help={__('Any registered post type, not just blog posts.', 'sage')}
          />
          <ToggleControl
            label={__('Show every entry', 'sage')}
            checked={showAll}
            onChange={(value) => setAttributes({ count: value ? 0 : 6 })}
          />
          {! showAll && (
            <RangeControl
              label={__('Number of entries', 'sage')}
              min={1}
              max={24}
              value={count}
              onChange={(value) => setAttributes({ count: value })}
            />
          )}
        </PanelBody>
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <ServerPreview block={metadata.name} attributes={attributes} icon="grid-view" label={__('Entry Grid', 'sage')} empty={__('Displays every published entry of the chosen post type as a card grid. Configure it in the block settings.', 'sage')} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
