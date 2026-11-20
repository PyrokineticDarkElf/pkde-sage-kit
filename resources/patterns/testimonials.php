<?php

/**
 * Title: Testimonials
 * Slug: pkde/testimonials
 * Categories: pkde
 * Keywords: testimonials, reviews, quotes, customers
 * Description: What customers say: product reviews (or quotes typed in) as a carousel, in the accent colour's light tint.
 */
echo \App\pattern_section('accent', 'light', <<<'HTML'
<!-- wp:sage/quotes {"heading":"What our customers say","source":"reviews","count":6,"minRating":4,"layout":"carousel"} /-->
HTML);
