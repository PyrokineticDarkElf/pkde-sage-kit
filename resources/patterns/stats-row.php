<?php

/**
 * Title: Stats: A row of figures
 * Slug: pkde/stats-row
 * Categories: pkde-stats
 * Keywords: stats, numbers, figures, facts
 * Description: A heading, an intro and four figures with their labels in a row, on the brand colour. Locked: change the words and the figures.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/feature-grid {"variant":"stats","columns":4,"centered":true,"eyebrow":"In numbers","heading":"A few figures about us","intro":"A line that puts the numbers below in context."} -->
<!-- wp:sage/feature-item {"heading":"120+","text":"Customers served"} /-->

<!-- wp:sage/feature-item {"heading":"15+","text":"Years in business"} /-->

<!-- wp:sage/feature-item {"heading":"4.9 / 5","text":"Average rating"} /-->

<!-- wp:sage/feature-item {"heading":"24 hours","text":"Typical reply time"} /-->
<!-- /wp:sage/feature-grid -->
HTML);
