<?php

/**
 * Title: Hero: Centred text
 * Slug: pkde/hero-centred
 * Categories: pkde-hero
 * Keywords: hero, header, banner, centred, text
 * Description: A centred heading, short text and two buttons, no picture, on the brand colour. Locked: change the words.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/hero {"layout":"text","headingLevel":1,"eyebrow":"Welcome","heading":"A clear promise, in a few words","content":"One or two sentences that say who you help and how. Replace this text with your own.","buttonText":"Get in touch","buttonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/"} /-->
HTML);
