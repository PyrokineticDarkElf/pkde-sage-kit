import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const STYLE_OPTIONS = [
  { label: __('Section (centered, full padding)', 'sage'), value: 'section' },
  { label: __('Banner (compact strip)', 'sage'), value: 'banner' },
  { label: __('Split (text beside a picture)', 'sage'), value: 'split' },
  { label: __('Boxed (a card in the section)', 'sage'), value: 'boxed' },
  { label: __('Link (one bold link with a circled arrow)', 'sage'), value: 'link' },
  { label: __('Promo (badge, title, button and a small picture in one row)', 'sage'), value: 'promo' },
  { label: __('Picture card (a picture behind the text, under a tint)', 'sage'), value: 'picture' },
];

const BUTTON_STYLE_OPTIONS = [
  { label: __('Primary', 'sage'), value: 'primary' },
  { label: __('Secondary', 'sage'), value: 'secondary' },
  { label: __('Text', 'sage'), value: 'text' },
];
const BUTTON_CLASSES = { primary: 'pkde-btn', secondary: 'pkde-btn-alt', text: 'pkde-btn pkde-btn--text' };

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
    buttonStyle,
    secondaryButtonStyle,
    imageUrl,
    imageId,
    imageAlt,
    badge,
  } = attributes;

  const isBanner = style === 'banner';
  const isLink = style === 'link';

  const blockProps = useBlockProps({
    className: isLink ? 'pkde-block pkde-cta pkde-cta--link' : [
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
          {style === 'promo' && <TextControl label={__('Badge', 'sage')} value={badge || ''} onChange={(value) => setAttributes({ badge: value })} />}
        </PanelBody>
        <PanelBody title={__('Logo or picture (optional)', 'sage')} initialOpen={false}>
          <MediaUploadCheck>
            <MediaUpload
              onSelect={(media) => setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || '' })}
              allowedTypes={['image']}
              value={imageId}
              render={({ open }) => <Button variant="secondary" onClick={open}>{imageUrl ? __('Change the picture', 'sage') : __('Choose a picture', 'sage')}</Button>}
            />
          </MediaUploadCheck>
          {imageUrl ? <Button variant="link" isDestructive onClick={() => setAttributes({ imageUrl: '', imageId: undefined, imageAlt: '' })}>{__('Remove it', 'sage')}</Button> : null}
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
          <SelectControl label={__('Button style', 'sage')} value={buttonStyle || 'primary'} options={BUTTON_STYLE_OPTIONS} onChange={(value) => setAttributes({ buttonStyle: value })} />
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
          <SelectControl label={__('Button style', 'sage')} value={secondaryButtonStyle || 'secondary'} options={BUTTON_STYLE_OPTIONS} onChange={(value) => setAttributes({ secondaryButtonStyle: value })} />
        </PanelBody>
      </InspectorControls>

      {isLink ? (
        <div {...blockProps}>
          <span className="pkde-cta__link">
            <span>{primaryButtonText || __('Link text (Primary button, in the sidebar)…', 'sage')}</span>
            <svg className="pkde-cta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" /><path d="M10.5 8l4 4-4 4" /></svg>
          </span>
        </div>
      ) : (
      <div {...blockProps}>
        {imageUrl && isBanner ? <img src={imageUrl} alt={imageAlt} className="pkde-cta-slot" /> : null}
        <div className={isBanner ? 'min-w-0' : 'mx-auto max-w-2xl'}>
          {imageUrl && !isBanner ? <img src={imageUrl} alt={imageAlt} className="pkde-cta-slot mx-auto mb-6" /> : null}
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
            <span className={BUTTON_CLASSES[buttonStyle || 'primary']}>{primaryButtonText || __('Button text…', 'sage')}</span>
          )}
          {secondaryButtonText && <span className={BUTTON_CLASSES[secondaryButtonStyle || 'secondary']}>{secondaryButtonText}</span>}
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
