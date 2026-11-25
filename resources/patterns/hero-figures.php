<?php

/**
 * Title: Hero: Split with figures
 * Slug: pkde/hero-figures
 * Categories: pkde-hero
 * Keywords: hero, header, banner, figures, stats
 * Description: A heading, a short text, two buttons and a picture, with three figures underneath. Locked: change the words, the figures and the picture.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/hero {"layout":"split","headingLevel":1,"eyebrow":"Welcome","heading":"A clear promise, in a few words","content":"One or two sentences that say who you help and how. Replace this text with your own.","buttonText":"Get in touch","buttonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/","imageSide":"left","placeholder":true} /-->

<!-- wp:sage/feature-grid {"variant":"stats","columns":3,"centered":true} -->
<!-- wp:sage/feature-item {"heading":"120+","text":"Customers served"} /-->

<!-- wp:sage/feature-item {"heading":"15+","text":"Years in business"} /-->

<!-- wp:sage/feature-item {"heading":"4.9 / 5","text":"Average rating"} /-->
<!-- /wp:sage/feature-grid -->
HTML);
