import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { CardColourPanel } from '../../js/card-colour-panel.jsx';

const TEMPLATE = [['sage/feature-item'], ['sage/feature-item'], ['sage/feature-item']];

function Edit({ attributes, setAttributes }) {
  const { eyebrow, heading, intro, columns, variant, centered, templateLock, cardTheme, cardButton } = attributes;
  const blockProps = useBlockProps({
    className: `pkde-block pkde-features pkde-features--${variant} pkde-features--cols-${columns}${centered ? ' pkde-features--centered' : ''}${cardTheme ? ` has-card-theme-${cardTheme}` : ''}`,
    style: {
      '--pkde-features-cols': columns,
      ...(cardButton ? { '--section-card-button-bg': `var(--color-${cardButton})`, '--section-card-button-text': `var(--color-${cardButton}-fg)`, '--section-card-button-border': `var(--color-${cardButton})` } : {}),
    },
  });
  const innerProps = useInnerBlocksProps({ className: 'pkde-features__grid' }, { allowedBlocks: ['sage/feature-item'], template: TEMPLATE, templateLock, orientation: 'horizontal' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Layout', 'sage')}>
          <RangeControl label={__('Columns', 'sage')} min={1} max={5} value={columns} onChange={(value) => setAttributes({ columns: value })} />
          <SelectControl
            label={__('Style', 'sage')}
            value={variant}
            options={[{ value: 'plain', label: __('Plain', 'sage') }, { value: 'cards', label: __('Each item in a box', 'sage') }]}
            onChange={(value) => setAttributes({ variant: value })}
          />
          <ToggleControl label={__('Centre the items', 'sage')} checked={!!centered} onChange={(value) => setAttributes({ centered: value })} />
          <TextControl label={__('Small heading above the title', 'sage')} value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
        </PanelBody>
        <CardColourPanel attributes={attributes} setAttributes={setAttributes} />
      </InspectorControls>

      <div {...blockProps}>
        <div className="pkde-features__head">
          {eyebrow ? <p className="pkde-eyebrow">{eyebrow}</p> : null}
          <RichText tagName="h2" className="pkde-features__title" value={heading} onChange={(value) => setAttributes({ heading: value })} placeholder={__('Section heading (optional)…', 'sage')} />
          <RichText tagName="p" className="pkde-features__intro" value={intro} onChange={(value) => setAttributes({ intro: value })} placeholder={__('A line of introduction (optional)…', 'sage')} />
        </div>
        <div {...innerProps} />
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
