import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

// The band has no fields on the page: its content is declared in config/pkde-sections.php. The editor shows the real band through ServerSideRender.
function Edit() {
  const ServerSideRender = window.wp?.serverSideRender;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Site-wide band', 'sage')}>
          <p>{__('This band is the same on every page. Its text, button, logo and colour are set once for the whole site, in the site\'s declaration file (config/pkde-sections.php), so there is nothing to edit here.', 'sage')}</p>
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {ServerSideRender
          ? <ServerSideRender block={metadata.name} EmptyResponsePlaceholder={() => <Placeholder icon="megaphone" label={__('Call-to-action band', 'sage')}>{__('Nothing is declared for it yet.', 'sage')}</Placeholder>} />
          : <Placeholder icon="megaphone" label={__('Call-to-action band', 'sage')} />}
      </div>
    </>
  );
}

registerBlockType(metadata, { edit: Edit, save: () => null });
