<?php

/**
 * Title: Call to action: Boxed card
 * Slug: pkde/cta-boxed
 * Categories: pkde-cta
 * Keywords: cta, call to action, card, box
 * Description: A heading, a line and two buttons in a card inside the section, in the accent colour's light tint. Locked: change the words and the links.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/cta {"style":"boxed","heading":"Ready to talk?","content":"Tell us what you need and we will get back to you.","primaryButtonText":"Contact us","primaryButtonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/"} /-->
HTML);
