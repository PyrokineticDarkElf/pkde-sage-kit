<?php
/**
 * Title: Call to action
 * Slug: pkde/cta
 * Categories: pkde
 * Keywords: cta, call to action, banner, contact
 * Description: A heading, a line of text and a button on the brand colour. Locked: change the words and the link.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/cta {"style":"section","heading":"Ready to talk?","content":"Tell us what you need and we will get back to you.","primaryButtonText":"Contact us","primaryButtonUrl":"/contact/"} /-->
HTML);
