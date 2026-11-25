<?php

/**
 * Title: Call to action: Split with a picture
 * Slug: pkde/cta-split
 * Categories: pkde-cta
 * Keywords: cta, call to action, picture, split
 * Description: A heading, a line and two buttons beside a picture that fills its half, on white. Locked: change the words, the links and the picture.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/cta {"style":"split","heading":"Ready to talk?","content":"Tell us what you need and we will get back to you.","primaryButtonText":"Contact us","primaryButtonUrl":"/contact/","secondaryButtonText":"Learn more","secondaryButtonUrl":"/about/"} /-->
HTML);
