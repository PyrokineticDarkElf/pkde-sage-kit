<?php

/**
 * Title: Logos: A single row
 * Slug: pkde/logos-row
 * Categories: pkde-logos
 * Keywords: logos, brands, clients, partners, trusted by
 * Description: A heading and a row of logos, on white. Locked: change the words and the logos.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/gallery {"heading":"Trusted by","description":"A line about who has worked with you.","columns":5,"aspectRatio":"4-3","enableLightbox":false,"placeholders":5,"fit":"contain"} /-->
HTML);
