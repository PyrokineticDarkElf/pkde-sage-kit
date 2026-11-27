import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, ToggleControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CategoryField, useProductCategories } from '../../js/product-category-fields.jsx';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';

// The same two lists the Recent, Best Selling and Featured blocks use (hero/edit.jsx explains the generated import).
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));
const CARD_STYLE_OPTIONS = [
  { label: __('White (default)', 'sage'), value: 'white' },
  { label: __('Tinted', 'sage'), value: 'tinted' },
  { label: __('Bold', 'sage'), value: 'bold' },
];

const ORDER_OPTIONS = [
  { value: 'menu_order', label: __('Shop order', 'sage') },
  { value: 'popularity', label: __('Best selling', 'sage') },
  { value: 'rating', label: __('Top rated', 'sage') },
  { value: 'date', label: __('Newest', 'sage') },
  { value: 'price', label: __('Price', 'sage') },
  { value: 'title', label: __('Name', 'sage') },
  { value: 'rand', label: __('Random', 'sage') },
];

function Preview({ attributes }) {
  const ServerSideRender = window.wp?.serverSideRender;

  if (!ServerSideRender) {
    return <Placeholder icon="products" label={__('Product Loop', 'sage')} />;
  }

  return (
    <ServerSideRender
      block={metadata.name}
      attributes={attributes}
      EmptyResponsePlaceholder={() => <Placeholder icon="products" label={__('Product Loop', 'sage')}>{__('No products match.', 'sage')}</Placeholder>}
    />
  );
}

function Edit({ attributes, setAttributes }) {
  const { limit, columns, source, orderby, order, categories, excludeCategories, heading, description, viewAll, theme, cardStyle, layout } = attributes;
  const available = useProductCategories();
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Products', 'sage')}>
          <SelectControl label={__('Show', 'sage')} value={source || 'all'} options={[{ value: 'all', label: __('Products, by category and order', 'sage') }, { value: 'featured', label: __('Only featured products', 'sage') }]} onChange={(value) => setAttributes({ source: value })} help={__('Featured products are the ones marked "This is a featured product" in the product editor.', 'sage')} />
          <RangeControl label={__('Number of products', 'sage')} min={1} max={48} value={limit} onChange={(value) => setAttributes({ limit: value })} />
          <RangeControl label={__('Columns', 'sage')} min={1} max={6} value={columns} onChange={(value) => setAttributes({ columns: value })} />
          <SelectControl label={__('Layout', 'sage')} value={layout || 'grid'} options={[{ value: 'grid', label: __('Grid', 'sage') }, { value: 'rail', label: __('Row that scrolls', 'sage') }, { value: 'feature', label: __('One large card beside a list', 'sage') }, { value: 'intro', label: __('Heading beside the products', 'sage') }]} onChange={(value) => setAttributes({ layout: value })} />
          <SelectControl label={__('Order by', 'sage')} value={orderby} options={ORDER_OPTIONS} onChange={(value) => setAttributes({ orderby: value })} />
          <SelectControl
            label={__('Sort direction', 'sage')}
            value={order}
            options={[{ value: 'ASC', label: __('Ascending', 'sage') }, { value: 'DESC', label: __('Descending', 'sage') }]}
            onChange={(value) => setAttributes({ order: value })}
          />
        </PanelBody>
        <PanelBody title={__('Categories', 'sage')}>
          <CategoryField
            label={__('Include categories', 'sage')}
            help={__('Only products in these categories. Leave empty for all.', 'sage')}
            value={categories}
            onChange={(value) => setAttributes({ categories: value })}
            categories={available}
          />
          <CategoryField
            label={__('Exclude categories', 'sage')}
            help={__('Leave out products in these categories.', 'sage')}
            value={excludeCategories}
            onChange={(value) => setAttributes({ excludeCategories: value })}
            categories={available}
          />
        </PanelBody>
        <PanelBody title={__('Section heading', 'sage')} initialOpen={false}>
          <TextControl label={__('Heading', 'sage')} value={heading} onChange={(value) => setAttributes({ heading: value })} help={__('Leave empty for no heading.', 'sage')} />
          <TextareaControl label={__('Description', 'sage')} value={description} onChange={(value) => setAttributes({ description: value })} />
          <ToggleControl label={__('Show a "View all products" link', 'sage')} checked={!!viewAll} onChange={(value) => setAttributes({ viewAll: value })} />
        </PanelBody>
        <PanelBody title={__('Section theme', 'sage')} initialOpen={false}>
          <SelectControl
            label={__('Theme', 'sage')}
            value={theme}
            options={THEME_OPTIONS}
            onChange={(value) => setAttributes({ theme: value })}
            help={__('Re-skins this section’s background and text colours to match a palette colour.', 'sage')}
          />
          <SelectControl
            label={__('Card style', 'sage')}
            value={cardStyle}
            options={CARD_STYLE_OPTIONS}
            onChange={(value) => setAttributes({ cardStyle: value })}
            help={__('Only visible once a Section theme above is set. Tinted/Bold recolour each card to match.', 'sage')}
          />
        </PanelBody>
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <Preview attributes={attributes} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
