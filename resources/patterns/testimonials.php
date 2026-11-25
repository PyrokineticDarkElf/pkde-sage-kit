<?php

/**
 * Title: Testimonials: Carousel
 * Slug: pkde/testimonials
 * Categories: pkde-testimonials
 * Keywords: testimonials, reviews, quotes, customers, carousel
 * Description: Quotes one after another as a carousel, in the accent colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/quotes {"eyebrow":"Kind words","heading":"What our customers say","source":"manual","layout":"carousel","items":[{"quote":"A short, honest line from a customer about what it was like to work with you.","name":"First name","role":"Their role or town","rating":5},{"quote":"Another short line: what they liked and what they would tell a friend.","name":"First name","role":"Their role or town","rating":5},{"quote":"A third short line, a different voice, saying something different.","name":"First name","role":"Their role or town","rating":5}]} /-->
HTML);
