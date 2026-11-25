<?php

/**
 * Title: Product categories: Tiles
 * Slug: pkde/categories-tiles
 * Categories: pkde-categories
 * Keywords: categories, shop, tiles, products
 * Description: A heading and the shop's categories as picture tiles, with a Shop all button, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/product-categories {"heading":"Shop by category","description":"A line that helps people choose where to start.","layout":"tiles","count":4} /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Shop all</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
HTML);
