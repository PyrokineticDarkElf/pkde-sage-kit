<?php

/**
 * Title: Contact: Compact strip
 * Slug: pkde/contact-strip
 * Categories: pkde-contact
 * Keywords: contact, strip, details, hours, social
 * Description: The phone, email and address, the hours and the social links in a row, no form, on the brand colour. Locked: change the words.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Call, write or visit</h3>
<!-- /wp:heading -->

<!-- wp:sage/site-contact /--></div>
<!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Opening hours</h3>
<!-- /wp:heading -->

<!-- wp:sage/site-hours /--></div>
<!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Follow us</h3>
<!-- /wp:heading -->

<!-- wp:sage/site-socials /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML);
