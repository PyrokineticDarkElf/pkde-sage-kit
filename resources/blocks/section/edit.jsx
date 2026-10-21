import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import metadata from './block.json';

// A Section is a full-width band whose content keeps the page width and gutter. Every setting is a native block support
// (background, text and link colour, border, padding, gap, content width from the layout), so this file only renders the
// wrapper. Its padding and gap defaults come from theme.json (styles.blocks["sage/section"]). Do not add settings here: anything
// else is a Group inside the Section. The List View name is editable with the block's own Rename option.
registerBlockType(metadata, {
  edit: () => <div {...useInnerBlocksProps(useBlockProps())} />,
  save: () => <div {...useInnerBlocksProps.save(useBlockProps.save())} />,
});
