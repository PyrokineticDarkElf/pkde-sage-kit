<?php

/**
 * Title: Testimonials: Three cards
 * Slug: pkde/testimonials-cards
 * Categories: pkde-testimonials
 * Keywords: testimonials, reviews, quotes, customers, cards
 * Description: Three quotes side by side in cards, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/quotes {"eyebrow":"Kind words","heading":"What our customers say","source":"manual","layout":"strip","items":[{"quote":"A short, honest line from a customer about what it was like to work with you.","name":"First name","role":"Their role or town","rating":5},{"quote":"Another short line: what they liked and what they would tell a friend.","name":"First name","role":"Their role or town","rating":5},{"quote":"A third short line, a different voice, saying something different.","name":"First name","role":"Their role or town","rating":5}]} /-->
HTML);
