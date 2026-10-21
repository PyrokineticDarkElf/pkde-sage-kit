<?php

namespace App;

use WP_Post;

/**
 * Estimates a post's reading time from rendered text plus a decreasing image
 * viewing allowance: 12 seconds for image one down to 3 seconds for image ten,
 * then 3 seconds for every subsequent image.
 */
class ReadingTime
{
    private const WORDS_PER_MINUTE = 250;

    /** @var array<int, string> */
    private static array $cache = [];

    public static function forPost(?WP_Post $post = null): string
    {
        $post = $post ?? get_post();

        if (! $post instanceof WP_Post) {
            return '';
        }

        if (isset(self::$cache[$post->ID])) {
            return self::$cache[$post->ID];
        }

        // Expand blocks and content filters before counting, so embedded images
        // and rendered copy contribute to the estimate as visitors see them.
        $content = apply_filters('the_content', $post->post_content);
        $wordCount = str_word_count(wp_strip_all_tags($content));
        $readingSeconds = ($wordCount / self::WORDS_PER_MINUTE) * MINUTE_IN_SECONDS;

        preg_match_all('/<img[^>]+>/i', $content, $images);
        $imageSeconds = 0;
        foreach ($images[0] as $index => $image) {
            $imageNumber = $index + 1;
            $imageSeconds += $imageNumber <= 10 ? 13 - $imageNumber : 3;
        }

        $minutes = max(1, (int) ceil(($readingSeconds + $imageSeconds) / MINUTE_IN_SECONDS));

        return self::$cache[$post->ID] = sprintf(
            _n('%s minute read', '%s minutes read', $minutes, 'sage'),
            $minutes,
        );
    }
}
