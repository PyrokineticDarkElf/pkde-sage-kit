<?php

/**
 * Title: Call to action: Centred band
 * Slug: pkde/cta
 * Categories: pkde-cta
 * Keywords: cta, call to action, banner, contact
 * Description: A centred heading, a line of text and two buttons on the brand colour. Locked: change the words and the links.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/cta {"style":"section","heading":"Ready to talk?","content":"Tell us what you need and we will get back to you.","primaryButtonText":"Contact us","primaryButtonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/"} /-->
HTML);
