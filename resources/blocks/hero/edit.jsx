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

// Generated fresh from config/brand-palette.json by bin/generate-theme-tokens.mjs
// every time it runs (i.e. every bin/setup run) — labelled with each colour's
// own real name ("Coral"), not a generic positional alias, and always exactly
// as many entries as the palette actually has. A hardcoded list here used to
// cap out at 6 generic entries (alpha–foxtrot) regardless of how many colours
// the palette actually had — silently wrong the moment a client build (like
// this template's own default palette) has more than 6.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

function Edit({ attributes, setAttributes }) {
  const { heading, content, buttonText, buttonUrl, imageUrl, imageId, imageAlt, theme } = attributes;

  const blockProps = useBlockProps({
    className: [
      'relative isolate flex min-h-[480px] flex-col justify-end overflow-hidden rounded-4xl pkde-section px-6 py-10 sm:px-10 sm:py-14 lg:min-h-[560px] lg:px-16 lg:py-16',
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
            help={__('Re-skins this section’s background, text, and button colours at once.', 'sage')}
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
            tagName="h2"
            className="font-display text-4xl font-semibold leading-[1.05] tracking-[-0.04em] sm:text-5xl lg:text-6xl"
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
            placeholder={__('Heading…', 'sage')}
          />

          <RichText
            tagName="p"
            className="mt-5 max-w-xl text-base leading-7 opacity-75 sm:text-lg"
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
