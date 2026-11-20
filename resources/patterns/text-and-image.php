<?php
/**
 * Title: Text and picture
 * Slug: pkde/text-and-image
 * Categories: pkde
 * Keywords: about, story, text, image, media
 * Description: A heading, text and a button beside a picture, on white. Locked: change the words and the picture.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/text-and-image {"eyebrow":"Our story","imageSide":"left","imageRatio":"4-3"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Tell the story behind the business</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Say how it started, what you care about and what makes your work different. Keep it short and in your own voice.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/about/">Read more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:sage/text-and-image -->
HTML);
