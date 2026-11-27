import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { SECTION_THEME_OPTIONS, CARD_BUTTON_OPTIONS } from '@generated/section-compat.js';

// The "Card colour" and "Card button colour" settings of every block with cards: Auto follows the section the block sits in; otherwise one of the site's Brand
// themes, which re-themes only the cards from that theme's own colours; the button colour is limited to the colours that work on the chosen card (generated with the palette).
export function CardColourPanel({ attributes, setAttributes }) {
  const { cardTheme = '', cardButton = '' } = attributes;
  const buttons = CARD_BUTTON_OPTIONS[cardTheme || 'auto'] || [];

  return (
    <PanelBody title={__('Cards', 'sage')} initialOpen={false}>
      <SelectControl
        label={__('Card colour', 'sage')}
        value={cardTheme}
        options={[{ value: '', label: __('Auto (follow the section)', 'sage') }, ...SECTION_THEME_OPTIONS]}
        onChange={(value) => setAttributes({ cardTheme: value, cardButton: '' })}
        help={__('Auto gives the cards the colours that suit the section. Pick a colour to make just these cards that colour.', 'sage')}
      />
      <SelectControl
        label={__('Card button colour', 'sage')}
        value={cardButton}
        options={[{ value: '', label: __('Auto', 'sage') }, ...buttons]}
        onChange={(value) => setAttributes({ cardButton: value })}
        help={__('Only colours that work on the chosen card are listed.', 'sage')}
      />
    </PanelBody>
  );
}
