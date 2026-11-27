import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { dispatch, select as dataSelect, subscribe, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { editorSetting } from './editor-settings.js';

// Motion in the editor. The two settings are block attributes registered in app/blocks.php; app/motion.php adds their classes to the page and the motion itself is CSS
// (motion.css) plus the reveal script. Who sees what:
//  - Administrators (the editor setting `pkdeMotionPanel`): the full Animation panel on every block, as before (hover lift, reveal);
//  - everyone else: ONE "Reveal on scroll" toggle, on Section and the kit's section blocks only (`pkdeMotionBlocks`);
//  - the site switch `pkde_kit/motion` ('on', 'reduced', 'off'): the toggle is only there when it is 'on'.
// Existing Fade up and Lift settings on any block keep rendering whoever can see the panel. Setting the toggle off stores an empty value, not nothing: "never set" is what a NEW
// Section is recognised by (below), and an Editor who turned the reveal off must not have it turned back on.
const SETTINGS = [
  { attribute: 'animationHover', label: 'On hover', options: [['None', ''], ['Lift', 'lift']] },
  { attribute: 'animationEntrance', label: 'When scrolled into view', options: [['None', ''], ['Fade up', 'fade-up']] },
];

const useMotion = () => useSelect((select) => ({
  mode: editorSetting(select, 'pkdeMotion', 'on'),
  panel: editorSetting(select, 'pkdeMotionPanel', false),
  blocks: editorSetting(select, 'pkdeMotionBlocks', []),
}), []);

function MotionControls({ name, attributes, setAttributes }) {
  const { mode, panel, blocks } = useMotion();

  if (panel) {
    return (
      <InspectorControls>
        <PanelBody title={__('Animation', 'sage')} initialOpen={false}>
          {SETTINGS.map(({ attribute, label, options }) => (
            <SelectControl
              key={attribute}
              label={label}
              value={attributes[attribute] ?? ''}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              options={options.map(([text, value]) => ({ label: text, value }))}
              onChange={(value) => setAttributes({ [attribute]: value || (attribute === 'animationEntrance' ? '' : undefined) })}
            />
          ))}
        </PanelBody>
      </InspectorControls>
    );
  }

  if (mode !== 'on' || !blocks.includes(name)) return null;

  return (
    <InspectorControls>
      <PanelBody title={__('Animation', 'sage')} initialOpen={false}>
        <ToggleControl
          label={__('Reveal on scroll', 'sage')}
          help={__('The section fades in and rises a little as it scrolls into view. Never the top of the page.', 'sage')}
          checked={Boolean(attributes.animationEntrance)}
          onChange={(value) => setAttributes({ animationEntrance: value ? 'fade-up' : '' })}
          __nextHasNoMarginBottom
        />
      </PanelBody>
    </InspectorControls>
  );
}

addFilter('editor.BlockEdit', 'sage/animation-panel', createHigherOrderComponent((BlockEdit) => (props) => (
  <>
    <BlockEdit {...props} />
    {props.isSelected && <MotionControls name={props.name} attributes={props.attributes} setAttributes={props.setAttributes} />}
  </>
), 'withAnimationPanel'));

// The same classes on the block in the canvas, so the editor shows the setting (there is no reveal script in the canvas: it shows the block as it rests).
addFilter('editor.BlockListBlock', 'sage/animation-preview', createHigherOrderComponent((BlockListBlock) => (props) => {
  const classes = [props.attributes.animationHover ? `pkde-hover-${props.attributes.animationHover}` : '', props.attributes.animationEntrance ? 'pkde-reveal' : ''];

  return <BlockListBlock {...props} className={[props.className, ...classes].filter(Boolean).join(' ')} />;
}, 'withAnimationPreview'));

// NEW Sections reveal on scroll by default (the owner's decision), and existing Sections keep what they have. A default in block.json cannot do that: WordPress leaves an
// attribute out of the saved comment when it equals the default, so every Section already on a page would turn the reveal on. Instead, once the editor has loaded, a Section
// that appears with the setting never set (a new one from the inserter, a pattern, a paste) gets "Fade up"; a Section that was in the content at load, or whose setting was
// ever set (an empty value means "off"), is left alone. The guard rails (never the hero or the first block) are applied by the page, not here.
let known = null;

subscribe(() => {
  const editor = dataSelect('core/editor');
  const store = dataSelect('core/block-editor');

  if (!editor?.__unstableIsEditorReady?.() || !store) return;

  const sections = [];
  const walk = (list) => list.forEach((block) => {
    if (block.name === 'sage/section') sections.push(block);
    walk(block.innerBlocks);
  });
  walk(store.getBlocks());

  if (known === null) {
    known = new Set(sections.map((block) => block.clientId));
    return;
  }

  const fresh = sections.filter((block) => !known.has(block.clientId));
  fresh.forEach((block) => known.add(block.clientId));

  if (!fresh.length || editorSetting(dataSelect, 'pkdeMotion', 'on') !== 'on') return;

  fresh.filter((block) => block.attributes.animationEntrance === undefined).forEach((block) => {
    dispatch('core/block-editor').__unstableMarkNextChangeAsNotPersistent?.();
    dispatch('core/block-editor').updateBlockAttributes(block.clientId, { animationEntrance: 'fade-up' });
  });
});
