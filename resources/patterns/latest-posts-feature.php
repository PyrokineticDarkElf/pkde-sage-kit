<?php

/**
 * Title: Latest posts: One large post and a list
 * Slug: pkde/latest-posts-feature
 * Categories: pkde-latest-posts
 * Keywords: posts, blog, news, latest, feature
 * Description: The latest post large beside a list of the next two, in the brand colour's light tint. Locked: change the words.
 */
echo \App\pattern_section('brand', 'light', <<<'HTML'
<!-- wp:sage/latest-posts {"heading":"From the blog","description":"A line about what you write about.","count":3,"layout":"feature"} /-->
HTML);
