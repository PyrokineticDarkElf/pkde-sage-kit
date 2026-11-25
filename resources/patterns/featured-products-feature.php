<?php

/**
 * Title: Featured products: One large card and a list
 * Slug: pkde/featured-products-feature
 * Categories: pkde-featured-products
 * Keywords: products, featured, shop, feature, list
 * Description: A heading, an intro, one large product beside a list of three, with a View all link, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/product-loop {"heading":"Featured products","description":"A line that says why these are worth a look.","limit":4,"columns":4,"viewAll":true,"layout":"feature"} /-->
HTML);
