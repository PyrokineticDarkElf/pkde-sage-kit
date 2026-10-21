import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';

import metadata from './block.json';

// The product list only exists on the shop itself (it is the shop's live, filtered archive), so the editor shows where it goes.
registerBlockType(metadata, {
  edit: () => (
    <div {...useBlockProps()}>
      <Placeholder icon="products" label={metadata.title} instructions={metadata.description} />
    </div>
  ),
});
