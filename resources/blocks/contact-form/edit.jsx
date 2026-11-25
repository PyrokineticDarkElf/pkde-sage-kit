import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Placeholder, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// The form belongs to the site (Contact Form 7, found by its key); the editor shows the real output where there is one.
function Edit({ attributes, setAttributes }) {
  const ServerSideRender = window.wp?.serverSideRender;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Form', 'sage')}>
          <TextControl label={__('Form key', 'sage')} help={__('The key of the Contact Form 7 form to show: contact is the contact page\'s form.', 'sage')} value={attributes.formKey} onChange={(value) => setAttributes({ formKey: value })} />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {ServerSideRender
          ? <ServerSideRender block={metadata.name} attributes={attributes} EmptyResponsePlaceholder={() => <Placeholder icon="email" label={__('Contact form', 'sage')}>{__('The site\'s contact form shows here once Contact Form 7 has a form with this key.', 'sage')}</Placeholder>} />
          : <Placeholder icon="email" label={__('Contact form', 'sage')} />}
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
