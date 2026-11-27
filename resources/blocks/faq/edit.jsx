import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, FormTokenField, Notice, Placeholder, TextControl, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { ServerPreview } from '../../js/server-preview.jsx';
import { editorSetting } from '../../js/editor-settings.js';

const ALLOWED_BLOCKS = ['sage/accordion-item'];
const TEMPLATE = [
  ['sage/accordion-item', { heading: __('Question one?', 'sage') }],
  ['sage/accordion-item', { heading: __('Question two?', 'sage') }],
  ['sage/accordion-item', { heading: __('Question three?', 'sage') }],
];

// The FAQ groups of the site, from the editor settings (App\faq.php): anyone who can edit the page may choose them, whether or not they may edit the FAQs.
function useFaqGroups() {
  return useSelect((select) => {
    return { groups: editorSetting(select, 'pkdeFaqGroups', []), adminUrl: editorSetting(select, 'pkdeFaqAdmin', '') };
  }, []);
}

// The groups the block shows, in display order: type a group's name, or pick it from the list. The order of the chips is the order on the page.
function GroupField({ value, onChange }) {
  const { groups } = useFaqGroups();
  const byId = new Map(groups.map((group) => [group.id, group]));
  const byName = new Map(groups.map((group) => [group.name, group]));

  return (
    <FormTokenField
      label={__('FAQ groups', 'sage')}
      value={value.map((id) => byId.get(id)?.name).filter(Boolean)}
      suggestions={groups.map((group) => group.name)}
      onChange={(tokens) => onChange(tokens.map((token) => byName.get(typeof token === 'string' ? token : token.value)?.id).filter(Boolean))}
      __experimentalExpandOnFocus
      __experimentalShowHowTo={false}
      __next40pxDefaultSize
      __nextHasNoMarginBottom
    />
  );
}

// "Edit these FAQs": only for people who may manage FAQs (the platform's manage_pkde_faqs; the address is empty for everyone else).
function EditFaqsLink({ groupIds }) {
  const { adminUrl } = useFaqGroups();

  if (!adminUrl) return null;

  const url = groupIds.length ? `${adminUrl}&faq_group=${groupIds[0]}` : adminUrl;

  return <p><a href={url} target="_blank" rel="noreferrer">{__('Edit these FAQs', 'sage')}</a></p>;
}

function Edit({ attributes, setAttributes, clientId }) {
  const { heading, description, columnThreshold, theme, cardStyle, groups = [], order = 'manual', limit = 0 } = attributes;
  // Unlike Hero (always a panel), the rounded/padded look is conditional —
  // see accordion/render.php's own comment on the same choice.
  const blockProps = useBlockProps({
    className: theme ? `rounded-4xl pkde-section theme-${theme} p-6 sm:p-8 lg:p-10` : undefined,
  });
  // Same one-marker-div trick as App\pkde_card_style_wrapper_open() on the
  // PHP side — every Accordion Item inside inherits --pkde-card-* via
  // ordinary CSS custom property inheritance, no per-item coordination.
  const CardStyleWrapper = cardStyle !== 'white' ? 'div' : Fragment;
  const cardStyleWrapperProps = cardStyle !== 'white' ? { className: `pkde-card-${cardStyle}` } : {};

  // Rough editor-canvas approximation of accordion-layout.blade.php's own
  // threshold switch — see accordion/edit.jsx's own comment on this same
  // hook for why it's only an approximation, not the source of truth.
  const itemCount = useSelect(
    (select) => select('core/block-editor').getBlockOrder(clientId).length,
    [clientId]
  );
  const useColumns = itemCount > columnThreshold;
  const groupMode = groups.length > 0;
  // blocks that already hold typed questions keep working and stay editable; the option to start one is gone
  const handTyped = !groupMode && itemCount > 0;

  // FAQs: the groups, and how they are ordered (the Section theme and the colours follow the Section the block sits in)
  const faqPanel = (
    <PanelBody title={__('FAQs', 'sage')}>
      <GroupField value={groups} onChange={(value) => setAttributes({ groups: value })} />
      {groupMode && (
        <>
          <SelectControl
            label={__('Order', 'sage')}
            value={order}
            options={[{ value: 'manual', label: __('As arranged in each group', 'sage') }, { value: 'az', label: __('A to Z', 'sage') }]}
            onChange={(value) => setAttributes({ order: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={__('Show at most (0 = all)', 'sage')}
            min={0}
            max={50}
            value={limit}
            onChange={(value) => setAttributes({ limit: value || 0 })}
          />
          <TextControl label={__('Heading', 'sage')} value={heading} onChange={(value) => setAttributes({ heading: value })} __next40pxDefaultSize __nextHasNoMarginBottom />
          <TextareaControl label={__('Description', 'sage')} value={description} onChange={(value) => setAttributes({ description: value })} __nextHasNoMarginBottom />
        </>
      )}
      <EditFaqsLink groupIds={groups} />
    </PanelBody>
  );

  return (
    <>
      <InspectorControls>
        {faqPanel}
        <PanelBody title={__('Layout', 'sage')}>
          <RangeControl
            label={__('Switch to columns above', 'sage')}
            help={__('Below this many items: heading beside the list. Above it: heading on top, items flow into newspaper-style columns.', 'sage')}
            min={2}
            max={20}
            value={columnThreshold}
            onChange={(value) => setAttributes({ columnThreshold: value })}
          />
        </PanelBody>
      </InspectorControls>

      {groupMode && (
        <div {...blockProps}>
          <ServerPreview
            block="sage/faq"
            attributes={attributes}
            icon="editor-help"
            label={__('FAQs', 'sage')}
            empty={__('The chosen groups have no published FAQs yet. Add them in the FAQs screen.', 'sage')}
          />
        </div>
      )}

      {!groupMode && !handTyped && (
        <div {...blockProps}>
          <Placeholder icon="editor-help" label={__('FAQs', 'sage')} instructions={__('Choose the FAQ groups to show here. The questions and answers are written once, in the FAQs screen, and show wherever their group is shown.', 'sage')}>
            <GroupField value={groups} onChange={(value) => setAttributes({ groups: value })} />
            <EditFaqsLink groupIds={groups} />
          </Placeholder>
        </div>
      )}

      {handTyped && (
      <div {...blockProps}>
        <Notice status="warning" isDismissible={false}>
          {__('Typing the questions into this block is deprecated: keep them as FAQs and pick their groups above, so every page that needs them shows the same answers.', 'sage')}
        </Notice>
        <CardStyleWrapper {...cardStyleWrapperProps}>
        {useColumns ? (
          <div>
            <div className="max-w-2xl">
              <RichText
                tagName="h2"
                className="font-display text-2xl font-semibold text-neutral-950 sm:text-3xl"
                value={heading}
                onChange={(value) => setAttributes({ heading: value })}
                allowedFormats={[]}
              />
              <RichText
                tagName="p"
                className="mt-2 text-neutral-600"
                placeholder={__('Description (optional)', 'sage')}
                value={description}
                onChange={(value) => setAttributes({ description: value })}
              />
            </div>
            <div className="mt-8 columns-1 gap-x-8 border-t-(length:--pkde-border-thin) border-neutral-200 sm:columns-2">
              <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} />
            </div>
          </div>
        ) : (
          <div className="lg:grid lg:grid-cols-2 lg:gap-x-12">
            <div className="lg:col-span-1">
              <RichText
                tagName="h2"
                className="font-display text-2xl font-semibold text-neutral-950 sm:text-3xl"
                value={heading}
                onChange={(value) => setAttributes({ heading: value })}
                allowedFormats={[]}
              />
              <RichText
                tagName="p"
                className="mt-2 text-neutral-600"
                placeholder={__('Description (optional)', 'sage')}
                value={description}
                onChange={(value) => setAttributes({ description: value })}
              />
            </div>
            <div className="mt-6 border-t-(length:--pkde-border-thin) border-neutral-200 lg:col-span-1 lg:mt-0">
              <InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} templateLock={false} />
            </div>
          </div>
        )}
        </CardStyleWrapper>
      </div>
      )}
    </>
  );
}

registerBlockType(metadata, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
});
