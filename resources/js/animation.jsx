import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

// The Animation panel on every block. The two settings are block attributes registered in app/blocks.php, which also adds their
// classes to the page; the motion itself is CSS in app.css (inside prefers-reduced-motion: no-preference). Add a choice by adding
// an option here and its rule there.
const SETTINGS = [
  { attribute: 'animationHover', prefix: 'pkde-hover-', label: 'On hover', options: [['None', ''], ['Lift', 'lift']] },
  { attribute: 'animationEntrance', prefix: 'pkde-entrance-', label: 'When scrolled into view', options: [['None', ''], ['Fade up', 'fade-up']] },
];

addFilter('editor.BlockEdit', 'sage/animation-panel', createHigherOrderComponent((BlockEdit) => (props) => (
  <>
    <BlockEdit {...props} />
    {props.isSelected && (
      <InspectorControls>
        <PanelBody title="Animation" initialOpen={false}>
          {SETTINGS.map(({ attribute, label, options }) => (
            <SelectControl
              key={attribute}
              label={label}
              value={props.attributes[attribute] ?? ''}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              options={options.map(([text, value]) => ({ label: text, value }))}
              onChange={(value) => props.setAttributes({ [attribute]: value || undefined })}
            />
          ))}
        </PanelBody>
      </InspectorControls>
    )}
  </>
), 'withAnimationPanel'));

// The same classes on the block in the canvas, so the editor shows the animation too.
addFilter('editor.BlockListBlock', 'sage/animation-preview', createHigherOrderComponent((BlockListBlock) => (props) => {
  const classes = SETTINGS.filter(({ attribute }) => props.attributes[attribute]).map(({ attribute, prefix }) => prefix + props.attributes[attribute]);

  return <BlockListBlock {...props} className={[props.className, ...classes].filter(Boolean).join(' ') || undefined} />;
}, 'withAnimationPreview'));
