<?php

/**
 * Title: Contact: Details beside the form
 * Slug: pkde/contact-beside-form
 * Categories: pkde-contact
 * Keywords: contact, form, details, phone, email, address
 * Description: The contact details and opening hours beside the contact form, on white. Locked: change the words; the details come from Site details.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Get in touch</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A line that says how quickly you reply and what to include in the message.</p>
<!-- /wp:paragraph -->

<!-- wp:sage/site-contact /-->

<!-- wp:sage/site-hours /--></div>
<!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:sage/contact-form {"formKey":"contact"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML);
