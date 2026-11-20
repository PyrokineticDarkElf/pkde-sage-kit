import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// The text side: ordinary blocks. A pattern locks the section to content only, so a client edits the words and the pictures, not the structure.
const TEMPLATE = [
  ['core/heading', { level: 2, placeholder: __('Heading…', 'sage') }],
  ['core/paragraph', { placeholder: __('Write about this…', 'sage') }],
  ['core/buttons', {}, [['core/button', { text: __('Learn more', 'sage') }]]],
];
const ALLOWED = ['core/heading', 'core/paragraph', 'core/list', 'core/buttons', 'core/quote', 'core/separator', 'core/spacer', 'core/group', 'sage/feature-grid'];

function Edit({ attributes, setAttributes }) {
  const { imageUrl, imageId, imageAlt, extraImages, imageSide, imageRatio, eyebrow, templateLock } = attributes;
  const blockProps = useBlockProps({ className: `pkde-block pkde-tai pkde-tai--ratio-${imageRatio}${imageSide === 'left' ? ' pkde-split--media-left' : ''}` });
  const innerProps = useInnerBlocksProps({ className: 'pkde-tai__blocks' }, { template: TEMPLATE, allowedBlocks: ALLOWED, templateLock });
  const pictures = imageUrl ? [{ url: imageUrl, alt: imageAlt }, ...(extraImages || [])] : [];
  const mosaic = pictures.length === 3;

  // 1 picture: the picture. 3 pictures: a mosaic. Any other number keeps the first.
  const choose = (media) => {
    const list = Array.isArray(media) ? media : [media];
    const [first, ...rest] = list;

    setAttributes({
      imageUrl: first.url,
      imageId: first.id,
      imageAlt: first.alt || '',
      extraImages: list.length >= 3 ? rest.slice(0, 2).map((item) => ({ id: item.id, url: item.url, alt: item.alt || '' })) : [],
    });
  };

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Picture', 'sage')}>
          <SelectControl
            label={__('Side', 'sage')}
            value={imageSide}
            options={[{ value: 'right', label: __('Picture on the right', 'sage') }, { value: 'left', label: __('Picture on the left', 'sage') }]}
            onChange={(value) => setAttributes({ imageSide: value })}
          />
          <SelectControl
            label={__('Shape', 'sage')}
            value={imageRatio}
            options={[
              { value: '4-3', label: __('Landscape (4:3)', 'sage') },
              { value: 'wide', label: __('Wide (16:9)', 'sage') },
              { value: 'square', label: __('Square', 'sage') },
              { value: 'portrait', label: __('Portrait (4:5)', 'sage') },
              { value: 'auto', label: __('As uploaded', 'sage') },
            ]}
            onChange={(value) => setAttributes({ imageRatio: value })}
          />
          <TextControl label={__('Small heading above the text', 'sage')} value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
          <MediaUploadCheck>
            <MediaUpload
              onSelect={choose}
              allowedTypes={['image']}
              multiple
              gallery={false}
              value={imageId}
              render={({ open }) => <Button variant="secondary" onClick={open}>{imageUrl ? __('Change pictures', 'sage') : __('Choose pictures (1, or 3 for a mosaic)', 'sage')}</Button>}
            />
          </MediaUploadCheck>
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className={`pkde-tai__grid${pictures.length ? ' pkde-split' : ''}`}>
          <div className="pkde-tai__content">
            {eyebrow ? <p className="pkde-eyebrow">{eyebrow}</p> : null}
            <div {...innerProps} />
          </div>
          <div className="pkde-split__media pkde-tai__media">
            {mosaic ? (
              <div className="pkde-tai__mosaic">{pictures.map((picture) => <img key={picture.url} src={picture.url} alt={picture.alt} />)}</div>
            ) : pictures[0] ? <img src={pictures[0].url} alt={pictures[0].alt} /> : null}
            <MediaUploadCheck>
              <MediaUpload
                onSelect={choose}
                allowedTypes={['image']}
                multiple
                gallery={false}
                value={imageId}
                render={({ open }) => <Button variant="secondary" onClick={open} className="mt-2">{imageUrl ? __('Change pictures', 'sage') : __('Add a picture', 'sage')}</Button>}
              />
            </MediaUploadCheck>
          </div>
        </div>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
