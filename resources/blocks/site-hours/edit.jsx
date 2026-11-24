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
          <ToggleControl label={__('Holidays and exceptions still to come', 'sage')} checked={attributes.showExceptions} onChange={(value) => setAttributes({ showExceptions: value })} />
        </PanelBody>
        <PanelBody title={__('Site details', 'sage')} initialOpen={false}>
          <p>{__('This block shows the details set once for the whole site, so there is nothing to type here.', 'sage')}</p>
          {settingsUrl && <p><a href={settingsUrl}>{__('Change them in Settings > Site details', 'sage')}</a></p>}
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {ServerSideRender
          ? <ServerSideRender block={metadata.name} attributes={attributes} EmptyResponsePlaceholder={() => <Placeholder icon="clock" label={__('Opening hours', 'sage')}>{__('Nothing is set yet: fill it in under Settings > Site details.', 'sage')}</Placeholder>} />
          : <Placeholder icon="clock" label={__('Opening hours', 'sage')} />}
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
