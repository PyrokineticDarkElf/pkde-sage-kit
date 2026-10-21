import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl, Placeholder, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
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
  const { heading, description, postType, count, theme, cardStyle } = attributes;
  const blockProps = useBlockProps();
  const showAll = count === 0;

  // Every public, UI-visible post type ('post' plus any registered CPT); 'attachment' is excluded as a sensible loop source.
  const postTypes = useSelect(
    (select) => (select('core').getPostTypes({ per_page: -1 }) ?? []).filter(
      (type) => type.viewable && type.slug !== 'attachment'
    ),
    []
  );

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section heading', 'sage')}>
          <TextControl
            label={__('Heading (optional)', 'sage')}
            value={heading}
            onChange={(value) => setAttributes({ heading: value })}
          />
          <TextareaControl
            label={__('Description (optional)', 'sage')}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
          />
        </PanelBody>
        <PanelBody title={__('Settings', 'sage')}>
          <SelectControl
            label={__('Post type', 'sage')}
            value={postType}
            options={postTypes.map((type) => ({ label: type.name, value: type.slug }))}
            onChange={(value) => setAttributes({ postType: value })}
            help={__('Any registered post type, not just blog posts.', 'sage')}
          />
          <ToggleControl
            label={__('Show every entry', 'sage')}
            checked={showAll}
            onChange={(value) => setAttributes({ count: value ? 0 : 6 })}
          />
          {! showAll && (
            <RangeControl
              label={__('Number of entries', 'sage')}
              min={1}
              max={24}
              value={count}
              onChange={(value) => setAttributes({ count: value })}
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
        <Placeholder icon="grid-view" label={__('Entry Grid', 'sage')}>
          {__('Displays every published entry of the chosen post type as a card grid. Configure it in the block settings.', 'sage')}
        </Placeholder>
      </div>
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => null,
});
