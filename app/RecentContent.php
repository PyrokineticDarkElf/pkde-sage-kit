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

    /**
     * The counts, per post type (`pkde_kit/recent_counts` may change them; a type that is not listed has no "New").
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        return function_exists('apply_filters') ? (array) apply_filters('pkde_kit/recent_counts', self::RECENT_COUNTS) : self::RECENT_COUNTS;
    }

    /** The "New" window in days for a post type (`pkde_kit/new_badge_days`, default 0 = off: the N newest items count instead). */
    public static function days(string $postType): int
    {
        return function_exists('apply_filters') ? max(0, (int) apply_filters('pkde_kit/new_badge_days', 0, $postType)) : 0;
    }

    /** @var array<string, int[]> Per-post-type recent-ID lists, computed once per request. */
    private static array $cache = [];

    public static function isNew(int $postId, ?string $postType = null): bool
    {
        $postType ??= get_post_type($postId);

        if (! $postType || ! isset(self::counts()[$postType])) {
            return false;
        }

        return in_array($postId, self::recentIds($postType), true);
    }

    /** Memoised so a grid of many cards of the same post type only queries once. */
    public static function recentIds(string $postType): array
    {
        if (! isset(self::$cache[$postType])) {
            $days = self::days($postType);
            self::$cache[$postType] = get_posts([
                'post_type' => $postType,
                'post_status' => 'publish',
                // N days: everything published in that window (capped); otherwise the N newest.
                'posts_per_page' => $days > 0 ? 50 : max(1, (int) (self::counts()[$postType] ?? 1)),
                'date_query' => $days > 0 ? [['after' => $days . ' days ago']] : [],
                'orderby' => 'date',
                'order' => 'DESC',
                'fields' => 'ids',
                'ignore_sticky_posts' => true,
                // a hidden / search-only product does not take one of the "N newest" slots
                'tax_query' => $postType === 'product' ? [catalog_visibility_clause()] : [],
                'no_found_rows' => true,
            ]);
        }

        return self::$cache[$postType];
    }
}
