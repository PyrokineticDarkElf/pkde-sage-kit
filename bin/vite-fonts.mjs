/**
 * The theme's font setup for vite.config.js, kept in the kit so a site carries one import instead of a copy.
 *
 *   import { google, local } from 'laravel-vite-plugin/fonts';
 *   import { buildFontDefinitions, fontDevServerBasePathFix } from './vendor/pkde/sage-kit/bin/vite-fonts.mjs';
 *   ...
 *   plugins: [tailwindcss(), fontDevServerBasePathFix(base), laravel({ ..., fonts: buildFontDefinitions({ google, local, paletteConfigPath }) })]
 *
 * buildFontDefinitions reads `fonts.{display,sans,mono}` of config/brand-palette.json (bin/generate-theme-tokens.mjs validates them) and turns each into a
 * laravel-vite-plugin font definition; the alias is always the slot name, so the CSS variable is --font-display / --font-sans / --font-mono, matching the utilities used
 * everywhere. A plain string is a Google Fonts family (weights 400, 500, 600, 700 unless the object form says otherwise); an object with `"source": "local"` and `src` or
 * `variants` self-hosts the files (laravel-vite-plugin's LocalFontOptions). `google` and `local` are passed in so this file needs no package of its own.
 *
 * fontDevServerBasePathFix works around laravel-vite-plugin's font dev middleware, which answers only `/__laravel_vite_plugin__/fonts/*` while the URLs it hands out
 * carry Vite's `base` (always set in a WordPress theme): it strips `base` off that one route before the middleware sees it. Register it before `laravel()`.
 * Production builds read fonts from the manifest and are not affected.
 */
import fs from 'node:fs';

export const DEFAULT_GOOGLE_WEIGHTS = [400, 500, 600, 700];

/** @param {{google: Function, local: Function, paletteConfigPath: string, weights?: number[]}} options */
export function buildFontDefinitions({ google, local, paletteConfigPath, weights = DEFAULT_GOOGLE_WEIGHTS }) {
  if (!fs.existsSync(paletteConfigPath)) return [];

  const fonts = JSON.parse(fs.readFileSync(paletteConfigPath, 'utf8')).fonts ?? {};
  const definitions = [];

  for (const alias of ['display', 'sans', 'mono']) {
    const config = fonts[alias];
    if (!config) continue;

    if (typeof config === 'string') {
      definitions.push(google(config, { alias, weights }));
      continue;
    }

    const { source, family, ...options } = config;
    definitions.push(source === 'local' ? local(family, { alias, ...options }) : google(family, { alias, weights, ...options }));
  }

  return definitions;
}

/** @param {string} base Vite's `base`, for example '/app/themes/sage/public/build/' */
export function fontDevServerBasePathFix(base) {
  const root = base.replace(/\/$/, '');
  const prefix = `${root}/__laravel_vite_plugin__/fonts/`;

  return {
    name: 'font-dev-server-base-path-fix',
    configureServer(server) {
      // registered directly, not as a post hook: Vite's own base handling would 404 the request before a post hook ever saw it
      server.middlewares.use((req, res, next) => {
        if (req.url?.startsWith(prefix)) req.url = req.url.slice(root.length);
        next();
      });
    },
  };
}
