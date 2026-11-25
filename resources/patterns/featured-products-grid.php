<?php

/**
 * Title: Featured products: Four across
 * Slug: pkde/featured-products-grid
 * Categories: pkde-featured-products
 * Keywords: products, featured, shop, grid
 * Description: A heading, an intro and four products in a row, with a View all link, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/product-loop {"heading":"Featured products","description":"A line that says why these are worth a look.","limit":4,"columns":4,"viewAll":true} /-->
HTML);
