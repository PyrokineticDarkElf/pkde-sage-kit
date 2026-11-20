<?php

/**
 * Title: Three features
 * Slug: pkde/feature-grid
 * Categories: pkde
 * Keywords: features, benefits, services, values, icons
 * Description: A heading and three items with an icon, a heading and a short text, on white. Locked: change the words, icons and links.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/feature-grid {"eyebrow":"Why us","heading":"What you can expect","intro":"A line that introduces the three things below.","columns":3,"centered":true} -->
<!-- wp:sage/feature-item {"icon":"check","heading":"Something to be proud of","text":"One or two sentences about this benefit."} /-->

<!-- wp:sage/feature-item {"icon":"heart","heading":"Something people love","text":"One or two sentences about this benefit."} /-->

<!-- wp:sage/feature-item {"icon":"shield","heading":"Something they can trust","text":"One or two sentences about this benefit."} /-->
<!-- /wp:sage/feature-grid -->
HTML);
