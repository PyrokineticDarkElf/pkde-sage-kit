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

const LAYOUT_OPTIONS = [
  { value: 'overlay', label: __('Full-width image, text over it', 'sage') },
  { value: 'split', label: __('Split: text beside the image', 'sage') },
  { value: 'text', label: __('Text only', 'sage') },
];

function Edit({ attributes, setAttributes }) {
  const {
    heading, content, buttonText, buttonUrl, imageUrl, imageId, imageAlt, theme,
    layout, imageSide, eyebrow, secondaryButtonText, secondaryButtonUrl, headingLevel,
  } = attributes;
  const overlay = layout !== 'split' && layout !== 'text';

  const blockProps = useBlockProps({
    className: overlay
      ? [
        'relative isolate flex min-h-[480px] flex-col justify-end overflow-hidden rounded-4xl pkde-section px-6 py-10 sm:px-10 sm:py-14 lg:min-h-[560px] lg:px-16 lg:py-16',
        theme ? `theme-${theme}` : '',
      ].filter(Boolean).join(' ')
      : `pkde-block pkde-hero pkde-hero--${layout}${layout === 'split' && imageSide === 'left' ? ' pkde-split--media-left' : ''}`,
  });

  const mediaButton = (label, className = 'mb-4') => (
    <MediaUploadCheck>
      <MediaUpload
        onSelect={(media) => setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || '' })}
        allowedTypes={['image']}
        value={imageId}
        render={({ open }) => (
          <Button variant="secondary" onClick={open} className={className}>{label}</Button>
        )}
      />
    </MediaUploadCheck>
  );

  const Heading = `h${Math.max(1, Math.min(4, Number(headingLevel) || 2))}`;

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Layout', 'sage')}>
          <SelectControl label={__('Layout', 'sage')} value={layout || 'overlay'} options={LAYOUT_OPTIONS} onChange={(value) => setAttributes({ layout: value })} />
          {layout === 'split' && (
            <SelectControl
              label={__('Image side', 'sage')}
              value={imageSide}
              options={[{ value: 'right', label: __('Right', 'sage') }, { value: 'left', label: __('Left', 'sage') }]}
              onChange={(value) => setAttributes({ imageSide: value })}
            />
          )}
          {!overlay && (
            <SelectControl
              label={__('Heading level', 'sage')}
              value={String(headingLevel || 2)}
              options={[{ value: '1', label: 'H1' }, { value: '2', label: 'H2' }, { value: '3', label: 'H3' }]}
              onChange={(value) => setAttributes({ headingLevel: Number(value) })}
              help={__('Use H1 for the main title of the page.', 'sage')}
            />
          )}
        </PanelBody>
        {overlay && (
          <PanelBody title={__('Section theme', 'sage')}>
            <SelectControl
              label={__('Theme', 'sage')}
              value={theme}
              options={THEME_OPTIONS}
              onChange={(value) => setAttributes({ theme: value })}
              help={__('Re-skins this section’s background, text, and button colours at once.', 'sage')}
            />
          </PanelBody>
        )}
        <PanelBody title={__('Buttons', 'sage')}>
          <TextControl label={__('Button text', 'sage')} value={buttonText} onChange={(value) => setAttributes({ buttonText: value })} />
          <TextControl label={__('Button URL', 'sage')} value={buttonUrl} onChange={(value) => setAttributes({ buttonUrl: value })} />
          {!overlay && (
            <>
              <TextControl label={__('Second button text', 'sage')} value={secondaryButtonText} onChange={(value) => setAttributes({ secondaryButtonText: value })} />
              <TextControl label={__('Second button URL', 'sage')} value={secondaryButtonUrl} onChange={(value) => setAttributes({ secondaryButtonUrl: value })} />
            </>
          )}
        </PanelBody>
      </InspectorControls>

      {overlay ? (
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
            {mediaButton(imageUrl ? __('Replace background image', 'sage') : __('Add background image', 'sage'))}

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
      ) : (
        <div {...blockProps}>
          <div className={`pkde-hero__grid${layout === 'split' ? ' pkde-split' : ''}`}>
            <div className="pkde-hero__copy">
              <RichText tagName="p" className="pkde-eyebrow" value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} placeholder={__('Eyebrow (optional)', 'sage')} allowedFormats={[]} />
              <RichText tagName={Heading} className="pkde-hero__title" value={heading} onChange={(value) => setAttributes({ heading: value })} placeholder={__('Heading…', 'sage')} />
              <RichText tagName="p" className="pkde-hero__text" value={content} onChange={(value) => setAttributes({ content: value })} placeholder={__('Supporting text…', 'sage')} />
              {(buttonText || secondaryButtonText) && (
                <div className="pkde-actions">
                  {buttonText && <span className="pkde-btn">{buttonText}</span>}
                  {secondaryButtonText && <span className="pkde-btn-alt">{secondaryButtonText}</span>}
                </div>
              )}
            </div>
            {layout === 'split' && (
              <div className="pkde-split__media">
                {imageUrl ? <img src={imageUrl} alt={imageAlt} /> : null}
                {mediaButton(imageUrl ? __('Replace image', 'sage') : __('Add image', 'sage'), 'mt-2')}
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
