<?php

/**
 * Title: Text and image: Picture right with a figure
 * Slug: pkde/text-image-figure
 * Categories: pkde-text-image
 * Keywords: about, story, text, image, figure, stat
 * Description: A heading, text and a button beside a picture with a figure laid over its corner, on the brand colour. Locked: change the words, the figure and the picture.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/text-and-image {"eyebrow":"Our story","imageSide":"right","imageRatio":"4-3","placeholder":1,"badgeValue":"15+","badgeLabel":"Years in business"} -->
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
