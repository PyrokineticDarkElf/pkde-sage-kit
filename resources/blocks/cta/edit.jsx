import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';

// Generated fresh from the actual palette — see hero/edit.jsx's own comment
// on this same import for why it's not a hardcoded list here.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

const STYLE_OPTIONS = [
  { label: __('Section (centered, full padding)', 'sage'), value: 'section' },
  { label: __('Banner (compact strip)', 'sage'), value: 'banner' },
];

function Edit({ attributes, setAttributes }) {
  const {
    style,
    theme,
    heading,
    content,
    primaryButtonText,
    primaryButtonUrl,
    secondaryButtonText,
    secondaryButtonUrl,
  } = attributes;

  const isBanner = style === 'banner';

  const blockProps = useBlockProps({
    className: [
      'pkde-section rounded-4xl px-6 py-10 sm:px-10 lg:px-16',
      isBanner ? 'flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between lg:py-8' : 'py-16 text-center lg:py-20',
      theme ? `theme-${theme}` : '',
    ].filter(Boolean).join(' '),
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Style', 'sage')}>
          <SelectControl
            label={__('Layout', 'sage')}
            value={style}
            options={STYLE_OPTIONS}
            onChange={(value) => setAttributes({ style: value })}
          />
        </PanelBody>
        <PanelBody title={__('Section theme', 'sage')}>
          <SelectControl
            label={__('Theme', 'sage')}
            value={theme}
            options={THEME_OPTIONS}
            onChange={(value) => setAttributes({ theme: value })}
            help={__('Re-skins this section’s background, text, and button colours at once.', 'sage')}
          />
        </PanelBody>
        <PanelBody title={__('Primary button', 'sage')}>
          <TextControl
            label={__('Text', 'sage')}
            value={primaryButtonText}
            onChange={(value) => setAttributes({ primaryButtonText: value })}
          />
          <TextControl
            label={__('URL', 'sage')}
            value={primaryButtonUrl}
            onChange={(value) => setAttributes({ primaryButtonUrl: value })}
          />
        </PanelBody>
        <PanelBody title={__('Secondary button (optional)', 'sage')} initialOpen={false}>
          <TextControl
            label={__('Text', 'sage')}
            value={secondaryButtonText}
            onChange={(value) => setAttributes({ secondaryButtonText: value })}
          />
          <TextControl
            label={__('URL', 'sage')}
            value={secondaryButtonUrl}
            onChange={(value) => setAttributes({ secondaryButtonUrl: value })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className={isBanner ? 'min-w-0' : 'mx-auto max-w-2xl'}>
          <RichText
            tagName="h2"
            className="font-display text-2xl font-semibold leading-tight tracking-[-0.03em] sm:text-3xl"
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
            placeholder={__('Heading…', 'sage')}
          />
          <RichText
            tagName="p"
            className="mt-3 text-base leading-7 opacity-75"
            value={content}
            onChange={(value) => setAttributes({ content: value })}
            placeholder={__('Supporting text…', 'sage')}
          />
        </div>

        <div className={isBanner ? 'flex flex-none flex-wrap gap-3' : 'mt-8 flex flex-wrap justify-center gap-3'}>
          {(primaryButtonText || !secondaryButtonText) && (
            <span className="pkde-btn">{primaryButtonText || __('Button text…', 'sage')}</span>
          )}
          {secondaryButtonText && <span className="pkde-btn-alt">{secondaryButtonText}</span>}
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
