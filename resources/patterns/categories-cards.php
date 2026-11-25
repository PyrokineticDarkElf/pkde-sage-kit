<?php

/**
 * Title: Product categories: Cards
 * Slug: pkde/categories-cards
 * Categories: pkde-categories
 * Keywords: categories, shop, cards, products
 * Description: A heading and the shop's categories as cards, with a Shop all button, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/product-categories {"heading":"Shop by category","description":"A line that helps people choose where to start.","layout":"cards","count":4} /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Shop all</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
HTML);
