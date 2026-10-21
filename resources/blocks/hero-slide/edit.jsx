import { registerBlockType } from '@wordpress/blocks';
import {
  useBlockProps,
  RichText,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';

// Generated fresh from the actual palette — see hero/edit.jsx's own comment
// on this same import for why it's not a hardcoded list here.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

const HEADING_LEVEL_OPTIONS = [
  { value: 'h1', label: __('H1 (page title, use on one slide only)', 'sage') },
  { value: 'h2', label: __('H2 (default)', 'sage') },
  { value: 'h3', label: __('H3', 'sage') },
  { value: 'h4', label: __('H4', 'sage') },
];

function Edit({ attributes, setAttributes }) {
  const { heading, headingLevel, content, buttonText, buttonUrl, imageUrl, imageId, imageAlt, theme } = attributes;

  const blockProps = useBlockProps({
    className: [
      'relative isolate flex min-h-[360px] flex-col justify-end overflow-hidden pkde-section px-6 py-10 sm:px-10 sm:py-14',
      theme ? `theme-${theme}` : '',
    ].filter(Boolean).join(' '),
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section theme', 'sage')}>
          <SelectControl
            label={__('Theme', 'sage')}
            value={theme}
            options={THEME_OPTIONS}
            onChange={(value) => setAttributes({ theme: value })}
            help={__('Re-skins this slide’s background, text, and button colours at once.', 'sage')}
          />
        </PanelBody>
        <PanelBody title={__('Heading', 'sage')}>
          <SelectControl
            label={__('Heading level', 'sage')}
            value={headingLevel}
            options={HEADING_LEVEL_OPTIONS}
            onChange={(value) => setAttributes({ headingLevel: value })}
            help={__('Exactly one slide on the page, usually the first, should be H1; the rest should stay H2.', 'sage')}
          />
        </PanelBody>
        <PanelBody title={__('Button', 'sage')}>
          <TextControl
            label={__('Button text', 'sage')}
            value={buttonText}
            onChange={(value) => setAttributes({ buttonText: value })}
          />
          <TextControl
            label={__('Button URL', 'sage')}
            value={buttonUrl}
            onChange={(value) => setAttributes({ buttonUrl: value })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {imageUrl && (
          <img
            src={imageUrl}
            alt={imageAlt}
            className="absolute inset-0 -z-20 h-full w-full object-cover"
          />
        )}
        <div className="absolute inset-0 -z-10 bg-gradient-to-t from-neutral-950 via-neutral-950/55 to-neutral-950/10" />

        <div className="max-w-2xl">
          <MediaUploadCheck>
            <MediaUpload
              onSelect={(media) =>
                setAttributes({
                  imageUrl: media.url,
                  imageId: media.id,
                  imageAlt: media.alt || '',
                })
              }
              allowedTypes={['image']}
              value={imageId}
              render={({ open }) => (
                <Button variant="secondary" onClick={open} className="mb-4">
                  {imageUrl
                    ? __('Replace background image', 'sage')
                    : __('Add background image', 'sage')}
                </Button>
              )}
            />
          </MediaUploadCheck>

          <RichText
            tagName={headingLevel || 'h2'}
            className="font-display text-3xl font-semibold leading-[1.05] tracking-[-0.04em] sm:text-4xl"
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
            placeholder={__('Heading…', 'sage')}
          />

          <RichText
            tagName="p"
            className="mt-4 max-w-xl text-base leading-7 opacity-75"
            value={content}
            onChange={(value) => setAttributes({ content: value })}
            placeholder={__('Supporting text…', 'sage')}
          />
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
