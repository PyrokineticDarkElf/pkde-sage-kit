<?php

/**
 * Title: About page
 * Slug: pkde/page-about
 * Categories: pkde-pages
 * Keywords: about, about us, page, starter
 * Block Types: core/post-content
 * Post Types: page
 * Description: An About page: an introduction, the story with a picture, what you stand for in three points, and a call to action. Every section is locked to its content.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/hero {"layout":"text","headingLevel":1,"eyebrow":"About us","heading":"Who we are and what we care about","content":"A short introduction: who you are, where you are and who you work for."} /-->
HTML);
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/text-and-image {"eyebrow":"Our story","imageSide":"right","imageRatio":"4-3","placeholder":1} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">How it started</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Tell the story in a few short paragraphs: why you began, what has changed and what has stayed the same.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/text-and-image -->
HTML);
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/feature-grid {"eyebrow":"What we stand for","heading":"Our values","columns":3,"centered":true} -->
<!-- wp:sage/feature-item {"icon":"heart","heading":"Care","text":"How you look after your customers."} /-->

<!-- wp:sage/feature-item {"icon":"leaf","heading":"Responsibility","text":"How you look after the world around you."} /-->

<!-- wp:sage/feature-item {"icon":"star","heading":"Quality","text":"What you will not compromise on."} /-->
<!-- /wp:sage/feature-grid -->
HTML);
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/cta {"style":"section","heading":"Want to know more?","content":"We would love to hear from you.","primaryButtonText":"Get in touch","primaryButtonUrl":"/contact/"} /-->
HTML);
