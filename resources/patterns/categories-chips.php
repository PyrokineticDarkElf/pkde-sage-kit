<?php

/**
 * Title: Product categories: Chips
 * Slug: pkde/categories-chips
 * Categories: pkde-categories
 * Keywords: categories, shop, chips, pills, products
 * Description: A heading and the shop's categories as a row of pills with their product counts, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/product-categories {"heading":"Shop by category","description":"A line that helps people choose where to start.","layout":"chips","count":8} /-->
HTML);
