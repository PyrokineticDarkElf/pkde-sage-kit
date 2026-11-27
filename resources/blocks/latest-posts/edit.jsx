import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl, TextControl, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';
import { ServerPreview } from '../../js/server-preview.jsx';

function Edit({ attributes, setAttributes }) {
  const { heading, description, count, postType, categoryId, layout, cardLook, pinViewAll, headingTab, source = 'latest' } = attributes;
  // The older layout values panel, overlay and label mean the grid with that card look
  const arrangement = ['feature', 'list', 'rail'].includes(layout) ? layout : 'cards';
  const look = arrangement === 'feature' || arrangement === 'list' ? '' : (cardLook || (['panel', 'overlay', 'label'].includes(layout) ? layout : ''));
  const blockProps = useBlockProps();

  const categories = useSelect(
    (select) => select('core').getEntityRecords('taxonomy', 'category', { per_page: -1 }) ?? [],
    []
  );

  // Every public, UI-visible post type ('post' plus any registered CPT) —
  // 'attachment' is technically public but never a sensible loop source, so
  // it's excluded explicitly rather than relying on viewable alone.
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
            label={__('Show', 'sage')}
            value={source}
            options={[
              { value: 'latest', label: __('The most recent', 'sage') },
              { value: 'sticky', label: __('The sticky posts (blog posts)', 'sage') },
              { value: 'manual', label: __('Every entry, in the order of their Order field', 'sage') },
            ]}
            onChange={(value) => setAttributes({ source: value, count: value === 'manual' ? 0 : Math.max(1, count) })}
            help={source === 'manual' ? __('A listing page: no "View all" link. Set the order in each entry\'s Page Attributes.', 'sage') : undefined}
          />
          <RangeControl
            label={source === 'manual' ? __('Number of entries (0 = all)', 'sage') : __('Number of posts', 'sage')}
            min={source === 'manual' ? 0 : 1}
            max={source === 'manual' ? 48 : 12}
            value={count}
            onChange={(value) => setAttributes({ count: value })}
          />
          <SelectControl
            label={__('Layout', 'sage')}
            value={arrangement}
            options={[
              { value: 'cards', label: __('Cards in a grid', 'sage') },
              { value: 'rail', label: __('Row that scrolls', 'sage') },
              { value: 'feature', label: __('One large post beside a list', 'sage') },
              { value: 'list', label: __('Compact dated list', 'sage') },
            ]}
            onChange={(value) => setAttributes({ layout: value, cardLook: value === 'feature' || value === 'list' ? '' : look })}
          />
          {(arrangement === 'cards' || arrangement === 'rail') && (
            <SelectControl
              label={__('Card look', 'sage')}
              value={look}
              options={[
                { value: '', label: __('The site\'s default card', 'sage') },
                { value: 'plain', label: __('Picture, title and text', 'sage') },
                { value: 'panel', label: __('Picture with a coloured panel: title, text, author', 'sage') },
                { value: 'overlay', label: __('Picture behind the text, under a dark tint', 'sage') },
                { value: 'label', label: __('Tall picture with a coloured label: title and category', 'sage') },
              ]}
              onChange={(value) => setAttributes({ cardLook: value, layout: arrangement })}
            />
          )}
          <ToggleControl label={__('Heading as a tab hanging from the top of the section', 'sage')} checked={!!headingTab} onChange={(value) => setAttributes({ headingTab: value })} />
          {look === 'panel' && (
            <ToggleControl label={__('Pin "View all" to the bottom of the section', 'sage')} checked={!!pinViewAll} onChange={(value) => setAttributes({ pinViewAll: value })} />
          )}
          <SelectControl
            label={__('Post type', 'sage')}
            value={postType}
            options={postTypes.map((type) => ({ label: type.name, value: type.slug }))}
            onChange={(value) => setAttributes({ postType: value, categoryId: value === 'post' ? categoryId : 0 })}
            help={__('Query any registered post type, not just blog posts.', 'sage')}
          />
          {postType === 'post' && (
            <SelectControl
              label={__('Category', 'sage')}
              value={categoryId}
              options={[
                { label: __('All categories', 'sage'), value: 0 },
                ...categories.map((category) => ({ label: category.name, value: category.id })),
              ]}
              onChange={(value) => setAttributes({ categoryId: Number(value) })}
            />
          )}
        </PanelBody>
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <ServerPreview block={metadata.name} attributes={attributes} icon="grid-view" label={__('Latest Posts', 'sage')} empty={__('Displays the most recent posts. Configure the count and category in the block settings.', 'sage')} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
