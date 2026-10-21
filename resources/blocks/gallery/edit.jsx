import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, Button, RangeControl, SelectControl, ToggleControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const ASPECT_OPTIONS = [
  { label: __('16:9 (video)', 'sage'), value: 'video' },
  { label: __('4:3', 'sage'), value: '4-3' },
  { label: __('1:1 (square)', 'sage'), value: 'square' },
];

function Edit({ attributes, setAttributes }) {
  const { heading, description, images, columns, aspectRatio, enableLightbox } = attributes;
  const blockProps = useBlockProps();

  const aspectClass = aspectRatio === 'square' ? 'aspect-square' : aspectRatio === '4-3' ? 'aspect-[4/3]' : 'aspect-video';

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Layout', 'sage')}>
          <RangeControl
            label={__('Columns (desktop)', 'sage')}
            min={2}
            max={6}
            value={columns}
            onChange={(value) => setAttributes({ columns: value })}
          />
          <SelectControl
            label={__('Image ratio', 'sage')}
            value={aspectRatio}
            options={ASPECT_OPTIONS}
            onChange={(value) => setAttributes({ aspectRatio: value })}
          />
          <ToggleControl
            label={__('Lightbox', 'sage')}
            help={__('Clicking an image opens it enlarged, with its own prev/next/close controls.', 'sage')}
            checked={enableLightbox}
            onChange={(value) => setAttributes({ enableLightbox: value })}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
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
          className="mt-2 max-w-2xl text-neutral-600"
          placeholder={__('Description (optional)', 'sage')}
          value={description}
          onChange={(value) => setAttributes({ description: value })}
        />

        <div className="mt-6">
          <MediaUploadCheck>
            <MediaUpload
              multiple
              gallery
              onSelect={(media) =>
                setAttributes({
                  images: media.map((item) => ({ id: item.id, url: item.sizes?.medium_large?.url || item.url, alt: item.alt || '' })),
                })
              }
              allowedTypes={['image']}
              value={images.map((image) => image.id)}
              render={({ open }) => (
                <Button variant="secondary" onClick={open}>
                  {images.length ? __('Edit gallery', 'sage') : __('Select images', 'sage')}
                </Button>
              )}
            />
          </MediaUploadCheck>

          {images.length > 0 ? (
            <div className={`mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-${columns}`}>
              {images.map((image) => (
                <div key={image.id} className={`${aspectClass} overflow-hidden rounded-2xl bg-neutral-100`}>
                  <img src={image.url} alt={image.alt} className="h-full w-full object-cover" />
                </div>
              ))}
            </div>
          ) : (
            <Placeholder icon="format-gallery" label={__('Image Gallery', 'sage')} className="mt-4">
              {__('Select images above to build the gallery.', 'sage')}
            </Placeholder>
          )}
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
