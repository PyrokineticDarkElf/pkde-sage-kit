import { InspectorControls } from '@wordpress/block-editor';
import { Button, Notice } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import { hasOwnColourOverTheme, clearOwnColours } from './section-colors-lib.js';

// A Group or Section with a Brand style takes the theme's colours. If it also has its own background or text colour, the theme wins on that
// block (css/section-themes.css); this says so in the block's settings and offers to remove the block's own colour.
addFilter('editor.BlockEdit', 'pkde/section-colour-notice', createHigherOrderComponent((BlockEdit) => (props) => (
  <>
    <BlockEdit {...props} />
    {props.isSelected && ['core/group', 'sage/section'].includes(props.name) && hasOwnColourOverTheme(props.attributes) && (
      <InspectorControls>
        <Notice status="warning" isDismissible={false}>
          {__('This block has its own background or text colour, but its Brand style sets the colours, so the block’s own colour is ignored. Remove it, or set the style to Default to use it.', 'sage')}
          <Button variant="link" onClick={() => props.setAttributes(clearOwnColours(props.attributes))}>{__('Remove the block’s own colour', 'sage')}</Button>
        </Notice>
      </InspectorControls>
    )}
  </>
), 'withSectionColourNotice'));
