<?php

/**
 * Title: Feature grid: Numbered steps
 * Slug: pkde/features-steps
 * Categories: pkde-features
 * Keywords: features, steps, process, how it works, numbered
 * Description: A heading, an intro and three numbered steps, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/feature-grid {"eyebrow":"How it works","heading":"Three simple steps","intro":"A line that introduces the points below.","centered":true,"variant":"steps","columns":3} -->
<!-- wp:sage/feature-item {"heading":"Get in touch","text":"One or two sentences about this step."} /-->

<!-- wp:sage/feature-item {"heading":"We agree the details","text":"One or two sentences about this step."} /-->

<!-- wp:sage/feature-item {"heading":"You enjoy the result","text":"One or two sentences about this step."} /-->
<!-- /wp:sage/feature-grid -->
HTML);
