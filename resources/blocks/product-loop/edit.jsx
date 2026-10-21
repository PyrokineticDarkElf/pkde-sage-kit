import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CategoryField, useProductCategories } from '../../js/product-category-fields.jsx';

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
  const { limit, columns, orderby, order, categories, excludeCategories } = attributes;
  const available = useProductCategories();
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Products', 'sage')}>
          <RangeControl label={__('Number of products', 'sage')} min={1} max={48} value={limit} onChange={(value) => setAttributes({ limit: value })} />
          <RangeControl label={__('Columns', 'sage')} min={1} max={6} value={columns} onChange={(value) => setAttributes({ columns: value })} />
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
