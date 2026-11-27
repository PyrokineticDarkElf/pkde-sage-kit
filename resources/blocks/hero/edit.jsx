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

const LAYOUT_OPTIONS = [
  { value: 'overlay', label: __('Full-width image, text over it', 'sage') },
  { value: 'split', label: __('Split: text beside the image', 'sage') },
  { value: 'text', label: __('Text only', 'sage') },
  { value: 'tinted', label: __('Full-width picture under a colour tint, text on the left', 'sage') },
];

const BUTTON_STYLE_OPTIONS = [
  { label: __('Primary', 'sage'), value: 'primary' },
  { label: __('Secondary', 'sage'), value: 'secondary' },
  { label: __('Text', 'sage'), value: 'text' },
];
const BUTTON_CLASSES = { primary: 'pkde-btn', secondary: 'pkde-btn-alt', text: 'pkde-btn pkde-btn--text' };

function Edit({ attributes, setAttributes }) {
  const {
    heading, content, buttonText, buttonUrl, imageUrl, imageId, imageAlt, theme,
    layout, imageSide, imageFit, eyebrow, secondaryButtonText, secondaryButtonUrl, headingLevel, buttonStyle, secondaryButtonStyle,
  } = attributes;
  const overlay = !['split', 'text', 'tinted'].includes(layout);

  const blockProps = useBlockProps({
    className: overlay
      ? [
        'relative isolate flex min-h-[480px] flex-col justify-end overflow-hidden rounded-4xl pkde-section px-6 py-10 sm:px-10 sm:py-14 lg:min-h-[560px] lg:px-16 lg:py-16',
        theme ? `theme-${theme}` : '',
      ].filter(Boolean).join(' ')
      : `pkde-block pkde-hero pkde-hero--${layout}${layout === 'split' && imageSide === 'left' ? ' pkde-split--media-left' : ''}${layout === 'split' && imageFit === 'contain' ? ' pkde-hero--fit-contain' : ''}`,
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
          {layout === 'split' && (
            <SelectControl
              label={__('Picture fit', 'sage')}
              value={imageFit === 'contain' ? 'contain' : 'cover'}
              options={[{ value: 'cover', label: __('Fill the space (crops the edges)', 'sage') }, { value: 'contain', label: __('Show the whole picture', 'sage') }]}
              onChange={(value) => setAttributes({ imageFit: value })}
              help={__('Use “Show the whole picture” for a cut-out on a transparent background, so its edges are not cropped.', 'sage')}
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
        <PanelBody title={__('Buttons', 'sage')}>
          <TextControl label={__('Button text', 'sage')} value={buttonText} onChange={(value) => setAttributes({ buttonText: value })} />
          <TextControl label={__('Button URL', 'sage')} value={buttonUrl} onChange={(value) => setAttributes({ buttonUrl: value })} />
          <SelectControl label={__('Button style', 'sage')} value={buttonStyle || 'primary'} options={BUTTON_STYLE_OPTIONS} onChange={(value) => setAttributes({ buttonStyle: value })} />
          {!overlay && (
            <>
              <TextControl label={__('Second button text', 'sage')} value={secondaryButtonText} onChange={(value) => setAttributes({ secondaryButtonText: value })} />
              <TextControl label={__('Second button URL', 'sage')} value={secondaryButtonUrl} onChange={(value) => setAttributes({ secondaryButtonUrl: value })} />
              <SelectControl label={__('Second button style', 'sage')} value={secondaryButtonStyle || 'secondary'} options={BUTTON_STYLE_OPTIONS} onChange={(value) => setAttributes({ secondaryButtonStyle: value })} />
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
                  {buttonText && <span className={BUTTON_CLASSES[buttonStyle || 'primary']}>{buttonText}</span>}
                  {secondaryButtonText && <span className={BUTTON_CLASSES[secondaryButtonStyle || 'secondary']}>{secondaryButtonText}</span>}
                </div>
              )}
            </div>
            {layout === 'tinted' && (
              <div className="pkde-hero__bg">
                {imageUrl ? <img src={imageUrl} alt={imageAlt} /> : null}
                {mediaButton(imageUrl ? __('Replace the background picture', 'sage') : __('Add the background picture', 'sage'), 'mt-2')}
              </div>
            )}
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
