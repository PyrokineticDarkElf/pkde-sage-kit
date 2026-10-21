import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const ALLOWED_BLOCKS = ['sage/hero-slide'];
const TEMPLATE = [
  ['sage/hero-slide', { heading: __('First slide', 'sage') }],
  ['sage/hero-slide', { heading: __('Second slide', 'sage') }],
  ['sage/hero-slide', { heading: __('Third slide', 'sage') }],
];

function Edit({ attributes, setAttributes }) {
  const { showArrows, showDots, loop, autoplay, autoplayDelay } = attributes;
  const blockProps = useBlockProps({ className: 'relative' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Navigation', 'sage')}>
          <ToggleControl
            label={__('Show arrows', 'sage')}
            checked={showArrows}
            onChange={(value) => setAttributes({ showArrows: value })}
          />
          <ToggleControl
            label={__('Show pagination dots', 'sage')}
            checked={showDots}
            onChange={(value) => setAttributes({ showDots: value })}
          />
          <ToggleControl
            label={__('Loop', 'sage')}
            help={__('Wrap around to the first slide after the last, and vice versa.', 'sage')}
            checked={loop}
            onChange={(value) => setAttributes({ loop: value })}
          />
        </PanelBody>
        <PanelBody title={__('Autoplay', 'sage')}>
          <ToggleControl
            label={__('Auto-advance slides', 'sage')}
            checked={autoplay}
            onChange={(value) => setAttributes({ autoplay: value })}
            help={__('Off by default. Visitors who prefer reduced motion never see it move, and a Pause button is shown.', 'sage')}
          />
          {autoplay && (
            <RangeControl
              label={__('Delay (seconds)', 'sage')}
              value={autoplayDelay / 1000}
              onChange={(value) => setAttributes({ autoplayDelay: (value ?? 5) * 1000 })}
              min={2}
              max={15}
            />
          )}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className="flex gap-4 overflow-x-auto pb-2">
          <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} orientation="horizontal" />
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
