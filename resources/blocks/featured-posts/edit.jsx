import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { THEME_OPTIONS as GENERATED_THEME_OPTIONS } from '@generated/theme-options.js';

// Generated fresh from the actual palette — see hero/edit.jsx's own comment
// on this same import for why it's not a hardcoded list here.
const THEME_OPTIONS = GENERATED_THEME_OPTIONS.map((option) => ({
  value: option.value,
  label: option.value ? option.label : __('Default', 'sage'),
}));

// See docs/planning/themed-cards-reference.md's "Proposed design" — 'white'
// is the plain card look this always had (no marker class at all); 'tinted'/
// 'bold' only actually do anything once a Section theme above is also set,
// via buildCardThemesCss()'s generated .theme-{name} .pkde-card-{style}
// rules (bin/generate-theme-tokens.mjs).
const CARD_STYLE_OPTIONS = [
  { label: __('White (default)', 'sage'), value: 'white' },
  { label: __('Tinted', 'sage'), value: 'tinted' },
  { label: __('Bold', 'sage'), value: 'bold' },
];

function Edit({ attributes, setAttributes }) {
  const { heading, description, source, count, postIds, theme, cardStyle } = attributes;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section heading', 'sage')}>
          <TextControl
            label={__('Heading', 'sage')}
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
          />
          <TextareaControl
            label={__('Description', 'sage')}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
          />
        </PanelBody>
        <PanelBody title={__('Settings', 'sage')}>
          <SelectControl
            label={__('Source', 'sage')}
            value={source}
            options={[
              { label: __('Posts stuck to the front page', 'sage'), value: 'sticky' },
              { label: __('Manually chosen', 'sage'), value: 'manual' },
            ]}
            onChange={(value) => setAttributes({ source: value })}
          />

          {source === 'sticky' && (
            <RangeControl
              label={__('Number of posts', 'sage')}
              min={1}
              max={12}
              value={count}
              onChange={(value) => setAttributes({ count: value })}
            />
          )}

          {source === 'manual' && (
            <TextControl
              label={__('Post IDs', 'sage')}
              help={__('Comma-separated post IDs, in the order you want them shown.', 'sage')}
              value={postIds}
              onChange={(value) => setAttributes({ postIds: value })}
            />
          )}
        </PanelBody>
        <PanelBody title={__('Section theme', 'sage')}>
          <SelectControl
            label={__('Theme', 'sage')}
            value={theme}
            options={THEME_OPTIONS}
            onChange={(value) => setAttributes({ theme: value })}
            help={__('Re-skins this section’s background and text colours to match a palette colour.', 'sage')}
          />
          <SelectControl
            label={__('Card style', 'sage')}
            value={cardStyle}
            options={CARD_STYLE_OPTIONS}
            onChange={(value) => setAttributes({ cardStyle: value })}
            help={__('Only visible once a Section theme above is set. Tinted/Bold recolour each card to match.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <Placeholder icon="star-filled" label={__('Featured Posts', 'sage')}>
          {__('Displays sticky or manually chosen posts. Configure the source in the block settings.', 'sage')}
        </Placeholder>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
