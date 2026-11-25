<?php

/**
 * Title: Latest posts: Compact list
 * Slug: pkde/latest-posts-list
 * Categories: pkde-latest-posts
 * Keywords: posts, blog, news, latest, list
 * Description: The latest three posts as a compact dated list, on the brand colour. Locked: change the words.
 */
echo \App\pattern_section('brand', 'solid', <<<'HTML'
<!-- wp:sage/latest-posts {"heading":"From the blog","description":"A line about what you write about.","count":3,"layout":"list"} /-->
HTML);
