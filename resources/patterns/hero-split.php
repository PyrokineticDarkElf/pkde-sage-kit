<?php
/**
 * Title: Hero, text beside a picture
 * Slug: pkde/hero-split
 * Categories: pkde
 * Keywords: hero, header, banner, intro
 * Description: A heading, a short text, buttons and a picture, in the brand colour's light tint. Locked: change the words and the picture.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/hero {"layout":"split","headingLevel":1,"eyebrow":"Welcome","heading":"A clear promise, in a few words","content":"One or two sentences that say who you help and how. Replace this text with your own.","buttonText":"Get in touch","buttonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/","imageSide":"right"} /-->
HTML);
