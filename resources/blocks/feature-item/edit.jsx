import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

function Edit({ attributes, setAttributes }) {
  const { icon, imageUrl, imageId, imageAlt, heading, text, linkText, linkUrl } = attributes;
  const icons = useSelect((select) => select('core/block-editor').getSettings().pkdeIcons || {}, []);
  const blockProps = useBlockProps({ className: 'pkde-feature' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Icon or picture', 'sage')}>
          <SelectControl
            label={__('Icon', 'sage')}
            value={icon}
            options={[{ value: '', label: __('None', 'sage') }, ...Object.keys(icons).map((key) => ({ value: key, label: key }))]}
            onChange={(value) => setAttributes({ icon: value })}
          />
          <MediaUploadCheck>
            <MediaUpload
              onSelect={(media) => setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || '' })}
              allowedTypes={['image']}
              value={imageId}
              render={({ open }) => <Button variant="secondary" onClick={open}>{imageUrl ? __('Change picture (replaces the icon)', 'sage') : __('Use a picture instead of an icon', 'sage')}</Button>}
            />
          </MediaUploadCheck>
          {imageUrl ? <Button variant="link" isDestructive onClick={() => setAttributes({ imageUrl: '', imageId: undefined, imageAlt: '' })}>{__('Remove the picture', 'sage')}</Button> : null}
        </PanelBody>
        <PanelBody title={__('Link', 'sage')}>
          <TextControl label={__('Link address', 'sage')} value={linkUrl} onChange={(value) => setAttributes({ linkUrl: value })} />
          <TextControl label={__('Link text', 'sage')} value={linkText} onChange={(value) => setAttributes({ linkText: value })} help={__('Empty: the heading itself is the link.', 'sage')} />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {imageUrl ? <div className="pkde-feature__media"><img src={imageUrl} alt={imageAlt} /></div> : icon && icons[icon] ? <span dangerouslySetInnerHTML={{ __html: icons[icon] }} /> : null}
        <RichText tagName="h3" className="pkde-feature__title" value={heading} onChange={(value) => setAttributes({ heading: value })} placeholder={__('Heading…', 'sage')} allowedFormats={['core/bold', 'core/italic']} />
        <RichText tagName="p" className="pkde-feature__text" value={text} onChange={(value) => setAttributes({ text: value })} placeholder={__('A short text…', 'sage')} />
        {linkUrl && linkText ? <span className="pkde-feature__link">{linkText}</span> : null}
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
