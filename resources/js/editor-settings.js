// The kit's own editor settings (PHP: the block_editor_settings_all filter) are kept by the post editor's `core/editor` store; the `core/block-editor` store keeps only the
// settings WordPress knows, so reading `pkdeIcons` or `pkdeFaqGroups` from there gives nothing. One place to read them from.
export const editorSetting = (select, key, fallback) => {
  const fromEditor = select('core/editor')?.getEditorSettings?.()?.[key];

  return fromEditor ?? select('core/block-editor')?.getSettings?.()?.[key] ?? fallback;
};
