import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Placeholder, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// Nothing to type: the block prints Site details (Settings > Site details) and takes its colours from the section it sits in. The editor shows the real output.
function Edit({ attributes, setAttributes }) {
  const ServerSideRender = window.wp?.serverSideRender;
  const blockProps = useBlockProps();
  const settingsUrl = (window.ajaxurl || '').replace('admin-ajax.php', 'options-general.php?page=pkde-site-details');

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Show', 'sage')}>
          <ToggleControl label={__('Phone numbers', 'sage')} checked={attributes.showPhones} onChange={(value) => setAttributes({ showPhones: value })} />
          <ToggleControl label={__('Email addresses', 'sage')} checked={attributes.showEmails} onChange={(value) => setAttributes({ showEmails: value })} />
          <ToggleControl label={__('Addresses', 'sage')} checked={attributes.showAddresses} onChange={(value) => setAttributes({ showAddresses: value })} />
          <ToggleControl label={__('In one centred row, with icons', 'sage')} checked={attributes.row} onChange={(value) => setAttributes({ row: value })} />
        </PanelBody>
        <PanelBody title={__('Site details', 'sage')} initialOpen={false}>
          <p>{__('This block shows the details set once for the whole site, so there is nothing to type here.', 'sage')}</p>
          {settingsUrl && <p><a href={settingsUrl}>{__('Change them in Settings > Site details', 'sage')}</a></p>}
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {ServerSideRender
          ? <ServerSideRender block={metadata.name} attributes={attributes} EmptyResponsePlaceholder={() => <Placeholder icon="phone" label={__('Contact details', 'sage')}>{__('Nothing is set yet: fill it in under Settings > Site details.', 'sage')}</Placeholder>} />
          : <Placeholder icon="phone" label={__('Contact details', 'sage')} />}
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
