<?php

/**
 * Title: Hero: Full-width picture
 * Slug: pkde/hero-overlay
 * Categories: pkde-hero
 * Keywords: hero, header, banner, picture, overlay
 * Description: A heading, a short text and two buttons over a full-width picture. Locked: change the words and the picture.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/hero {"layout":"overlay","headingLevel":1,"eyebrow":"","heading":"A clear promise, in a few words","content":"One or two sentences that say who you help and how. Replace this text with your own.","buttonText":"Get in touch","buttonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/","placeholder":true} /-->
HTML);
