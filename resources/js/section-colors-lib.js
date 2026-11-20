// The pure parts of the section colour picker (resources/js/section-colors.js): no WordPress imports, so Node can test them.

/** The section theme id ("brand-sage-light") of the nearest styled ancestor of a block, or null. `store` is the block editor store, `compat` the generated list. */
export function sectionThemeOf(store, clientId, compat) {
  for (const parentId of store.getBlockParents(clientId, true)) {
    const className = store.getBlockAttributes(parentId)?.className ?? '';
    const match = /(?:^|\s)is-style-(brand-[a-z0-9-]+)(?:\s|$)/.exec(className);

    if (match && compat[match[1]]) return match[1];
  }

  return null;
}

/** The theme palette with the colours readable on the section first, in the order given; the order of the rest is kept and nothing is removed. */
export function compatibleFirst(palette, compatible) {
  const rank = new Map(compatible.map((slug, index) => [slug, index]));
  const position = (swatch) => (rank.has(swatch.slug) ? rank.get(swatch.slug) : Infinity);

  return [...palette].sort((a, b) => (position(a) === position(b) ? 0 : position(a) < position(b) ? -1 : 1));
}

/** True when a block carries a Brand style AND its own background or text colour, which the theme overrides (css/section-themes.css). */
export function hasOwnColourOverTheme(attributes) {
  const color = attributes?.style?.color;

  return /(?:^|\s)is-style-brand-/.test(attributes?.className ?? '')
    && Boolean(attributes.backgroundColor || attributes.textColor || color?.background || color?.text || color?.gradient || attributes.gradient);
}

/** The attributes that remove a block's own background and text colour. */
export function clearOwnColours(attributes) {
  const { background, text, gradient, ...color } = attributes?.style?.color ?? {};
  const { color: _color, ...style } = attributes?.style ?? {};

  return {
    backgroundColor: undefined,
    textColor: undefined,
    gradient: undefined,
    style: Object.keys(color).length ? { ...style, color } : (Object.keys(style).length ? style : undefined),
  };
}
