<?php

/**
 * Title: Product categories: Tiles
 * Slug: pkde/categories-tiles
 * Categories: pkde-categories
 * Keywords: categories, shop, tiles, products
 * Description: A heading and the shop's categories as picture tiles, with on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/product-categories {"heading":"Shop by category","description":"A line that helps people choose where to start.","layout":"tiles","count":4} /-->
HTML);
