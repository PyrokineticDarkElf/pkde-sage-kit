<?php

/**
 * Title: Logos: A grid
 * Slug: pkde/logos-grid
 * Categories: pkde-logos
 * Keywords: logos, brands, clients, partners, grid
 * Description: A heading and two rows of logos, in the brand colour's light tint. Locked: change the words and the logos.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/gallery {"heading":"Brands we work with","description":"A line about who has worked with you.","columns":4,"aspectRatio":"4-3","enableLightbox":false,"placeholders":8,"fit":"contain"} /-->
HTML);
