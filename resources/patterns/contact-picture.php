<?php

/**
 * Title: Contact: Picture and details
 * Slug: pkde/contact-picture
 * Categories: pkde-contact
 * Keywords: contact, picture, map, details, address
 * Description: A picture beside the contact details, hours and social links, in the brand colour's light tint. Locked: change the words and the picture.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/text-and-image {"imageSide":"left","imageRatio":"4-3","placeholder":1} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Get in touch</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A line that says how quickly you reply and what to include in the message.</p>
<!-- /wp:paragraph -->

<!-- wp:sage/site-contact /-->

<!-- wp:sage/site-socials /-->
<!-- /wp:sage/text-and-image -->
HTML);
