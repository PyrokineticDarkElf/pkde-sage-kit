<?php

/**
 * Title: Feature grid: Picture cards
 * Slug: pkde/features-cards
 * Categories: pkde-features
 * Keywords: features, cards, pictures, services, grid
 * Description: A heading, an intro and four cards with a picture, a heading and a short text, two by two. Locked: change the words and the pictures.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/feature-grid {"eyebrow":"What we do","heading":"Our services","intro":"A line that introduces the points below.","centered":false,"variant":"cards","columns":2} -->
<!-- wp:sage/feature-item {"placeholder":true,"heading":"The first thing we do","text":"One or two sentences about this service."} /-->

<!-- wp:sage/feature-item {"placeholder":true,"heading":"The second thing we do","text":"One or two sentences about this service."} /-->

<!-- wp:sage/feature-item {"placeholder":true,"heading":"The third thing we do","text":"One or two sentences about this service."} /-->

<!-- wp:sage/feature-item {"placeholder":true,"heading":"The fourth thing we do","text":"One or two sentences about this service."} /-->
<!-- /wp:sage/feature-grid -->
HTML);
