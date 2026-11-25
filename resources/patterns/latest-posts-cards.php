<?php

/**
 * Title: Latest posts: Three cards
 * Slug: pkde/latest-posts-cards
 * Categories: pkde-latest-posts
 * Keywords: posts, blog, news, latest, cards
 * Description: A heading and the three latest posts as cards, with an All posts button, on white. Locked: change the words.
 */
echo \App\pattern_section('white', 'solid', <<<'HTML'
<!-- wp:sage/latest-posts {"heading":"From the blog","description":"A line about what you write about.","count":3} /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/blog/">All posts</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
HTML);
