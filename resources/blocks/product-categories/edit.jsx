import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, Placeholder, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';
import { CategoryField, useProductCategories } from '../../js/product-category-fields.jsx';

// Generated fresh from the actual palette — see hero/edit.jsx's own comment
// on this same import for why it's not a hardcoded list here.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

function Edit({ attributes, setAttributes }) {
  const { heading, description, count, theme, layout, include, exclude, labels, extra } = attributes;
  const blockProps = useBlockProps();
  const available = useProductCategories();

  const setExtra = (index, patch) => setAttributes({ extra: extra.map((tile, i) => (i === index ? { ...tile, ...patch } : tile)) });

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
            label={__('Number of categories', 'sage')}
            help={__('0 shows every category.', 'sage')}
            min={0}
            max={12}
            value={count}
            onChange={(value) => setAttributes({ count: value })}
          />
        </PanelBody>
        <PanelBody title={__('Layout and categories', 'sage')} initialOpen={false}>
          <SelectControl
            label={__('Layout', 'sage')}
            value={layout}
            options={[
              { value: 'cards', label: __('Cards (name and count over the photo)', 'sage') },
              { value: 'tiles', label: __('Tiles (large photo, name beneath)', 'sage') },
            ]}
            onChange={(value) => setAttributes({ layout: value })}
          />
          <CategoryField
            label={__('Include categories', 'sage')}
            help={__('Show only these, in this order. Leave empty to show every category that has products.', 'sage')}
            value={include}
            onChange={(value) => setAttributes({ include: value })}
            categories={available}
          />
          <CategoryField
            label={__('Exclude categories', 'sage')}
            help={__('Never show these.', 'sage')}
            value={exclude}
            onChange={(value) => setAttributes({ exclude: value })}
            categories={available}
          />
        </PanelBody>
        {include.length > 0 && (
          <PanelBody title={__('Category labels', 'sage')} initialOpen={false}>
            {include.map((slug) => (
              <TextControl
                key={slug}
                label={available.find((c) => c.slug === slug)?.name ?? slug}
                help={__('Leave empty to use the category name.', 'sage')}
                value={labels?.[slug] ?? ''}
                onChange={(value) => setAttributes({ labels: { ...labels, [slug]: value } })}
              />
            ))}
          </PanelBody>
        )}
        {layout === 'tiles' && (
          <PanelBody title={__('Extra tiles', 'sage')} initialOpen={false}>
            {extra.map((tile, index) => (
              <div key={index} style={{ marginBottom: 16, paddingBottom: 8, borderBottom: '1px solid #ddd' }}>
                <TextControl label={__('Label', 'sage')} value={tile.label ?? ''} onChange={(value) => setExtra(index, { label: value })} />
                <TextControl label={__('Link URL', 'sage')} value={tile.url ?? ''} onChange={(value) => setExtra(index, { url: value })} />
                <TextControl label={__('Image URL', 'sage')} value={tile.image ?? ''} onChange={(value) => setExtra(index, { image: value })} />
                <Button variant="link" isDestructive onClick={() => setAttributes({ extra: extra.filter((_, i) => i !== index) })}>{__('Remove', 'sage')}</Button>
              </div>
            ))}
            <Button variant="secondary" onClick={() => setAttributes({ extra: [...extra, { label: '', url: '', image: '', alt: '' }] })}>{__('Add a tile', 'sage')}</Button>
          </PanelBody>
        )}
        <PanelBody title={__('Section theme', 'sage')}>
          <SelectControl
            label={__('Theme', 'sage')}
            value={theme}
            options={THEME_OPTIONS}
            onChange={(value) => setAttributes({ theme: value })}
            help={__('Re-skins this section’s background and text colours to match a palette colour.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <Placeholder icon="category" label={__('Product Categories', 'sage')}>
          {__('Displays a card grid of product categories (WooCommerce only). Configure the count in the block settings.', 'sage')}
        </Placeholder>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
