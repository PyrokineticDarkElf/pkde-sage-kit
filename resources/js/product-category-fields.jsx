import { useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { FormTokenField } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Every WooCommerce product category, name and slug (the REST API caps a page at 100, so it walks the pages).
 */
export function useProductCategories() {
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      const all = [];

      for (let page = 1; page <= 10; page += 1) {
        try {
          const batch = await apiFetch({ path: `/wp/v2/product_cat?per_page=100&page=${page}&_fields=slug,name,parent&hide_empty=false` });
          all.push(...batch);
          if (batch.length < 100) break;
        } catch (error) {
          break;
        }
      }

      if (!cancelled) setCategories(all.map((term) => ({ slug: term.slug, name: decodeEntities(term.name) })));
    })();

    return () => { cancelled = true; };
  }, []);

  return categories;
}

/**
 * Choose categories by name, stored as slugs, in the order they were added.
 */
export function CategoryField({ label, help, value = [], onChange, categories }) {
  const names = useMemo(() => {
    const counts = {};
    categories.forEach((c) => { counts[c.name] = (counts[c.name] || 0) + 1; });

    return categories.map((c) => ({ ...c, display: counts[c.name] > 1 ? `${c.name} (${c.slug})` : c.name }));
  }, [categories]);

  const toDisplay = (slug) => names.find((c) => c.slug === slug)?.display ?? slug;
  const toSlug = (token) => names.find((c) => c.display === token)?.slug;

  return (
    <FormTokenField
      label={label}
      value={value.map(toDisplay)}
      suggestions={names.map((c) => c.display)}
      onChange={(tokens) => onChange(tokens.map(toSlug).filter(Boolean))}
      __experimentalExpandOnFocus
      __experimentalShowHowTo={false}
      help={help}
    />
  );
}
