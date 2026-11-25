<?php

/**
 * Title: Stats: Figures beside a picture
 * Slug: pkde/stats-picture
 * Categories: pkde-stats
 * Keywords: stats, numbers, figures, facts, picture
 * Description: A heading and three figures beside a picture, in the accent colour's light tint. Locked: change the words, the figures and the picture.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/text-and-image {"eyebrow":"In numbers","imageSide":"right","imageRatio":"square","placeholder":1} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">A few figures about us</h2>
<!-- /wp:heading -->

<!-- wp:sage/feature-grid {"variant":"stats","columns":3} -->
<!-- wp:sage/feature-item {"heading":"120+","text":"Customers served"} /-->

<!-- wp:sage/feature-item {"heading":"15+","text":"Years in business"} /-->

<!-- wp:sage/feature-item {"heading":"4.9 / 5","text":"Average rating"} /-->
<!-- /wp:sage/feature-grid -->
<!-- /wp:sage/text-and-image -->
HTML);
