import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';

const TEMPLATE = [['sage/feature-item'], ['sage/feature-item'], ['sage/feature-item']];

function Edit({ attributes, setAttributes }) {
  const { eyebrow, heading, intro, columns, variant, centered, templateLock, cardTheme, cardButton, linkText, linkUrl, loop, autoplay, autoplayDelay, speed, showDots, showArrows } = attributes;
  const blockProps = useBlockProps({
    className: `pkde-block pkde-features pkde-features--${variant} pkde-features--cols-${columns}${centered ? ' pkde-features--centered' : ''}${cardTheme ? ` has-card-theme-${cardTheme}` : ''}`,
    style: {
      '--pkde-features-cols': columns,
      ...(cardButton ? { '--section-card-button-bg': `var(--color-${cardButton})`, '--section-card-button-text': `var(--color-${cardButton}-fg)`, '--section-card-button-border': `var(--color-${cardButton})` } : {}),
    },
  });
  const innerProps = useInnerBlocksProps({ className: variant === 'people' ? 'pkde-features__track' : 'pkde-features__grid' }, { allowedBlocks: ['sage/feature-item'], template: TEMPLATE, templateLock, orientation: 'horizontal' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Layout', 'sage')}>
          <RangeControl label={__('Columns', 'sage')} min={1} max={5} value={columns} onChange={(value) => setAttributes({ columns: value })} />
          <SelectControl
            label={__('Style', 'sage')}
            value={variant}
            options={[{ value: 'plain', label: __('Plain', 'sage') }, { value: 'cards', label: __('Each item in a box', 'sage') }, { value: 'people', label: __('People: picture cards in a row that scrolls', 'sage') }]}
            onChange={(value) => setAttributes({ variant: value })}
          />
          <ToggleControl label={__('Centre the items', 'sage')} checked={!!centered} onChange={(value) => setAttributes({ centered: value })} />
          <TextControl label={__('Small heading above the title', 'sage')} value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
          <TextControl label={__('Link under the items: text', 'sage')} value={linkText} onChange={(value) => setAttributes({ linkText: value })} />
          <TextControl label={__('Link under the items: address', 'sage')} value={linkUrl} onChange={(value) => setAttributes({ linkUrl: value })} />
        </PanelBody>
        {variant === 'people' && (
          <PanelBody title={__('Carousel', 'sage')} initialOpen={false}>
            <ToggleControl label={__('Dots under the row', 'sage')} checked={showDots !== false} onChange={(value) => setAttributes({ showDots: value })} />
            <ToggleControl label={__('Previous and next arrows', 'sage')} checked={!!showArrows} onChange={(value) => setAttributes({ showArrows: value })} />
            <ToggleControl label={__('Go round again at the end', 'sage')} checked={!!loop} onChange={(value) => setAttributes({ loop: value })} />
            <ToggleControl label={__('Move on its own', 'sage')} checked={!!autoplay} onChange={(value) => setAttributes({ autoplay: value })} help={__('Stops while the pointer is over the row or focus is in it, and for visitors who asked for less motion. A pause button is shown.', 'sage')} />
            {autoplay && (
              <RangeControl label={__('Seconds between moves', 'sage')} min={1} max={15} step={0.5} value={(autoplayDelay || 5000) / 1000} onChange={(value) => setAttributes({ autoplayDelay: (value ?? 5) * 1000 })} />
            )}
            <SelectControl
              label={__('How fast a move is', 'sage')}
              value={speed || 'normal'}
              options={[{ value: 'slow', label: __('Slow', 'sage') }, { value: 'normal', label: __('Normal', 'sage') }, { value: 'fast', label: __('Fast', 'sage') }]}
              onChange={(value) => setAttributes({ speed: value })}
            />
          </PanelBody>
        )}
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <div className="pkde-features__head">
          {eyebrow ? <p className="pkde-eyebrow">{eyebrow}</p> : null}
          <RichText tagName="h2" className="pkde-features__title" value={heading} onChange={(value) => setAttributes({ heading: value })} placeholder={__('Section heading (optional)…', 'sage')} />
          <RichText tagName="p" className="pkde-features__intro" value={intro} onChange={(value) => setAttributes({ intro: value })} placeholder={__('A line of introduction (optional)…', 'sage')} />
        </div>
        {variant === 'people' ? <div className="pkde-features__grid"><div {...innerProps} /></div> : <div {...innerProps} />}
        {linkUrl && linkText ? <p className="pkde-features__more"><span>{linkText}</span></p> : null}
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
