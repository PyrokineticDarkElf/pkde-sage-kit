<?php

/**
 * Title: Testimonials: One large quote
 * Slug: pkde/testimonials-panel
 * Categories: pkde-testimonials
 * Keywords: testimonials, reviews, quote, customers, large
 * Description: One large quote with its author, in the accent colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/quotes {"eyebrow":"Kind words","heading":"What our customers say","source":"manual","layout":"panel","items":[{"quote":"A short, honest line from a customer about what it was like to work with you.","name":"First name","role":"Their role or town","rating":5}]} /-->
HTML);
