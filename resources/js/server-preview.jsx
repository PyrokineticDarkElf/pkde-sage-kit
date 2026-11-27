import { Placeholder } from '@wordpress/components';

// The editor's live preview of a server-rendered block (trial): the same markup the page prints, through ServerSideRender, so the section's colours, a
// card colour and the grid look the way they will. A block with nothing to show (no products yet, WooCommerce off) says what it would show instead of an empty box.
export function ServerPreview({ block, attributes, icon, label, empty }) {
  const ServerSideRender = window.wp?.serverSideRender;

  if (!ServerSideRender) {
    return <Placeholder icon={icon} label={label}>{empty}</Placeholder>;
  }

  return (
    <ServerSideRender
      block={block}
      attributes={attributes}
      EmptyResponsePlaceholder={() => <Placeholder icon={icon} label={label}>{empty}</Placeholder>}
    />
  );
}
