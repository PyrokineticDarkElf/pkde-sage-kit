import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';

// Generated fresh from the actual palette — see hero/edit.jsx's own comment
// on this same import for why it's not a hardcoded list here.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

// See docs/planning/themed-cards-reference.md's "Proposed design" — 'white'
// is the plain card look (no marker class at all); 'tinted'/'bold' only
// actually do anything once a Section theme above is also set, via
// buildCardThemesCss()'s generated .theme-{name} .pkde-card-{style} rules
// (bin/generate-theme-tokens.mjs).
const CARD_STYLE_OPTIONS = [
  { label: __('White (default)', 'sage'), value: 'white' },
  { label: __('Tinted', 'sage'), value: 'tinted' },
  { label: __('Bold', 'sage'), value: 'bold' },
];

const ALLOWED_BLOCKS = ['sage/accordion-item'];
const TEMPLATE = [
  ['sage/accordion-item', { heading: __('First item', 'sage') }],
  ['sage/accordion-item', { heading: __('Second item', 'sage') }],
  ['sage/accordion-item', { heading: __('Third item', 'sage') }],
];

function Edit({ attributes, setAttributes, clientId }) {
  const { heading, description, columnThreshold, theme, cardStyle } = attributes;
  // Unlike Hero (always a panel), the rounded/padded look is conditional —
  // see accordion/render.php's own comment on the same choice.
  const blockProps = useBlockProps({
    className: theme ? `rounded-4xl pkde-section theme-${theme} p-6 sm:p-8 lg:p-10` : undefined,
  });
  // Same one-marker-div trick as App\pkde_card_style_wrapper_open() on the
  // PHP side — every Accordion Item inside inherits --pkde-card-* via
  // ordinary CSS custom property inheritance, no per-item coordination.
  const CardStyleWrapper = cardStyle !== 'white' ? 'div' : Fragment;
  const cardStyleWrapperProps = cardStyle !== 'white' ? { className: `pkde-card-${cardStyle}` } : {};

  // Rough editor-canvas approximation of accordion-layout.blade.php's own
  // threshold switch — the actual layout is decided server-side per request
  // (render.php counts sage/accordion-item children the same way), this
  // just keeps the editor from looking wildly different from the frontend.
  const itemCount = useSelect(
    (select) => select('core/block-editor').getBlockOrder(clientId).length,
    [clientId]
  );
  const useColumns = itemCount > columnThreshold;

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Layout', 'sage')}>
          <RangeControl
            label={__('Switch to columns above', 'sage')}
            help={__('Below this many items: heading beside the list. Above it: heading on top, items flow into newspaper-style columns.', 'sage')}
            min={2}
            max={20}
            value={columnThreshold}
            onChange={(value) => setAttributes({ columnThreshold: value })}
          />
        </PanelBody>
        <PanelBody title={__('Section theme', 'sage')}>
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
            help={__('Only visible once a Section theme above is set. Colours each item’s own toggle to match.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <CardStyleWrapper {...cardStyleWrapperProps}>
        {useColumns ? (
          <div>
            <div className="max-w-2xl">
              <RichText
                tagName="h2"
                className="font-display text-2xl font-semibold text-neutral-950 sm:text-3xl"
                placeholder={__('Heading (optional)', 'sage')}
                value={heading}
                onChange={(value) => setAttributes({ heading: value })}
                allowedFormats={[]}
              />
              <RichText
                tagName="p"
                className="mt-2 text-neutral-600"
                placeholder={__('Description (optional)', 'sage')}
                value={description}
                onChange={(value) => setAttributes({ description: value })}
              />
            </div>
            <div className="mt-8 columns-1 gap-x-8 border-t border-neutral-200 sm:columns-2">
              <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} />
            </div>
          </div>
        ) : (
          <div className="lg:grid lg:grid-cols-2 lg:gap-x-12">
            <div className="lg:col-span-1">
              <RichText
                tagName="h2"
                className="font-display text-2xl font-semibold text-neutral-950 sm:text-3xl"
                placeholder={__('Heading (optional)', 'sage')}
                value={heading}
                onChange={(value) => setAttributes({ heading: value })}
                allowedFormats={[]}
              />
              <RichText
                tagName="p"
                className="mt-2 text-neutral-600"
                placeholder={__('Description (optional)', 'sage')}
                value={description}
                onChange={(value) => setAttributes({ description: value })}
              />
            </div>
            <div className="mt-6 border-t border-neutral-200 lg:col-span-1 lg:mt-0">
              <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} />
            </div>
          </div>
        )}
        </CardStyleWrapper>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
