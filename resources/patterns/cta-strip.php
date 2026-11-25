<?php

/**
 * Title: Call to action: Inline strip
 * Slug: pkde/cta-strip
 * Categories: pkde-cta
 * Keywords: cta, call to action, strip, banner
 * Description: A heading and a line of text with the buttons beside them, in one strip, in the brand colour's light tint. Locked: change the words and the links.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/cta {"style":"banner","heading":"Ready to talk?","content":"Tell us what you need and we will get back to you.","primaryButtonText":"Contact us","primaryButtonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/"} /-->
HTML);
