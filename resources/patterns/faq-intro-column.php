<?php

/**
 * Title: FAQ: Intro beside the questions
 * Slug: pkde/faq-intro-column
 * Categories: pkde-faq
 * Keywords: faq, questions, answers, accordion, help
 * Description: The heading and intro in a column beside a list of questions that open to their answers, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/accordion {"heading":"Questions we are often asked","description":"A line that says where to go if the answer is not here.","columnThreshold":99} -->
<!-- wp:sage/accordion-item {"heading":"How does it work?"} -->
<!-- wp:paragraph -->
<p>A clear answer in one or two sentences. Replace it with your own.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/accordion-item -->

<!-- wp:sage/accordion-item {"heading":"How long does it take?"} -->
<!-- wp:paragraph -->
<p>A clear answer in one or two sentences. Replace it with your own.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/accordion-item -->

<!-- wp:sage/accordion-item {"heading":"What does it cost?"} -->
<!-- wp:paragraph -->
<p>A clear answer in one or two sentences. Replace it with your own.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/accordion-item -->

<!-- wp:sage/accordion-item {"heading":"Can I change my mind?"} -->
<!-- wp:paragraph -->
<p>A clear answer in one or two sentences. Replace it with your own.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/accordion-item -->

<!-- wp:sage/accordion-item {"heading":"How do I get in touch?"} -->
<!-- wp:paragraph -->
<p>A clear answer in one or two sentences. Replace it with your own.</p>
<!-- /wp:paragraph -->
<!-- /wp:sage/accordion-item -->
<!-- /wp:sage/accordion -->
HTML);
