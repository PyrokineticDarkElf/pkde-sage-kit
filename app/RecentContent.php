<?php

namespace App;

/**
 * "Is this post among the N most recently published items of its own post
 * type" — computed at render time, not stored. Replaces a much heavier
 * design (a stored "New" taxonomy term, a WP-Cron job trimming it back
 * down on a schedule, an admin settings page) with a stateless read: no
 * term is ever created or removed, and there's nothing depending on
 * WP-Cron, which only ever runs on an incoming request and can go quiet
 * for a long stretch on a low-traffic site — exactly the sites this
 * template targets, and exactly when a "new post" cue matters most.
 */
class RecentContent
{
    /**
     * How many of each post type's most recent published items count as
     * "new" — edit this to add a post type or change the count. Not an
     * admin-editable setting on purpose: this is a small, fixed display
     * rule, not something that needs its own settings screen.
     */
    private const RECENT_COUNTS = [
        'post' => 3,
        'product' => 4,
    ];

    /** @var array<string, int[]> Per-post-type recent-ID lists, computed once per request. */
    private static array $cache = [];

    public static function isNew(int $postId, ?string $postType = null): bool
    {
        $postType ??= get_post_type($postId);

        if (! $postType || ! isset(self::RECENT_COUNTS[$postType])) {
            return false;
        }

        return in_array($postId, self::recentIds($postType), true);
    }

    /** Memoised so a grid of many cards of the same post type only queries once. */
    public static function recentIds(string $postType): array
    {
        if (! isset(self::$cache[$postType])) {
            self::$cache[$postType] = get_posts([
                'post_type' => $postType,
                'post_status' => 'publish',
                'posts_per_page' => self::RECENT_COUNTS[$postType],
                'orderby' => 'date',
                'order' => 'DESC',
                'fields' => 'ids',
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);
        }

        return self::$cache[$postType];
    }
}
