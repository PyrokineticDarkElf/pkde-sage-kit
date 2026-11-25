<?php

/**
 * Title: Featured products: Heading beside three products
 * Slug: pkde/featured-products-intro
 * Categories: pkde-featured-products
 * Keywords: products, featured, shop, intro
 * Description: The heading and intro in a column beside three products, with a View all link, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/product-loop {"heading":"Featured products","description":"A line that says why these are worth a look.","limit":3,"columns":3,"viewAll":true,"layout":"intro"} /-->
HTML);
