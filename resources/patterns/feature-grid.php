<?php

/**
 * Title: Feature grid: Icons
 * Slug: pkde/feature-grid
 * Categories: pkde-features
 * Keywords: features, benefits, services, values, icons
 * Description: A heading, an intro and three items with an icon, a heading and a short text, on white. Locked: change the words, icons and links.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/feature-grid {"eyebrow":"Why us","heading":"What you can expect","intro":"A line that introduces the points below.","centered":true,"columns":3} -->
<!-- wp:sage/feature-item {"icon":"check","heading":"Something to be proud of","text":"One or two sentences about this benefit."} /-->

<!-- wp:sage/feature-item {"icon":"heart","heading":"Something people love","text":"One or two sentences about this benefit."} /-->

<!-- wp:sage/feature-item {"icon":"shield","heading":"Something they can trust","text":"One or two sentences about this benefit."} /-->
<!-- /wp:sage/feature-grid -->
HTML);
