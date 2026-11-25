<?php

/**
 * Title: Featured products: Row that scrolls
 * Slug: pkde/featured-products-rail
 * Categories: pkde-featured-products
 * Keywords: products, featured, shop, rail, carousel, scroll
 * Description: A heading, an intro and a row of products that scrolls sideways, with a View all link, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/product-loop {"heading":"Featured products","description":"A line that says why these are worth a look.","limit":6,"columns":4,"viewAll":true,"layout":"rail"} /-->
HTML);
