<?php

/**
 * Which legacy things do a site's pages still use? READ-ONLY: it only reads post_content (published, draft, pending, scheduled and private posts of every type,
 * and reusable blocks) and prints a small table. Nothing is written, nothing is sent anywhere; the output holds counts and post ids only.
 *
 *   wp eval-file web/app/themes/sage/vendor/pkde/sage-kit/bin/content-usage.php
 *
 * It lists, for each of the following, how many posts use it, how many times, and the first post ids:
 *   - blocks that are hidden from the inserter and will be removed (Recent Products, Best Selling Products, Featured Products, Featured Posts, Carousel, CTA Band,
 *     Entry Grid), and the Gallery and Hero Slider blocks that are being reviewed for removal;
 *   - the `theme` attribute on a kit block (the Section theme setting the editors no longer offer): one row per block;
 *   - the `cardStyle` attribute set to tinted or bold;
 *   - the `.theme-*` classes, `pkde-card-tinted` and `pkde-card-bold` written into content.
 * A row with 0 posts is safe to remove from the kit; a row with posts says which pages to look at before it is.
 */

namespace Pkde\SageKit\Tools;

const CONTENT_USAGE_HIDDEN_BLOCKS = [
    'sage/recent-products', 'sage/best-selling-products', 'sage/featured-products', 'sage/featured-posts', 'sage/carousel', 'sage/cta-band', 'sage/entry-grid',
    'sage/gallery', 'sage/hero-slider',
];

/**
 * @param  iterable<array{ID: int|string, post_content: string}>  $posts
 * @return array<string, array{posts: list<int>, uses: int}> label => the post ids that use it and the number of uses
 */
function content_usage(iterable $posts): array
{
    $rows = [];
    $add = function (string $label, int $id, int $uses = 1) use (&$rows) {
        $rows[$label] ??= ['posts' => [], 'uses' => 0];
        $rows[$label]['uses'] += $uses;

        if (! in_array($id, $rows[$label]['posts'], true)) {
            $rows[$label]['posts'][] = $id;
        }
    };

    foreach (CONTENT_USAGE_HIDDEN_BLOCKS as $name) {
        $rows["block {$name}"] = ['posts' => [], 'uses' => 0];
    }

    foreach (['theme-* class', 'pkde-card-tinted class', 'pkde-card-bold class', 'cardStyle tinted', 'cardStyle bold'] as $label) {
        $rows[$label] = ['posts' => [], 'uses' => 0];
    }

    foreach ($posts as $post) {
        $id = (int) $post['ID'];
        $content = (string) $post['post_content'];

        if (preg_match_all('~<!--\s+wp:(sage/[a-z0-9-]+)(?:\s+(\{.*?\}))?\s+/?-->~s', $content, $blocks, PREG_SET_ORDER)) {
            foreach ($blocks as $block) {
                $name = $block[1];
                $attributes = isset($block[2]) ? json_decode($block[2], true) : null;

                if (in_array($name, CONTENT_USAGE_HIDDEN_BLOCKS, true)) {
                    $add("block {$name}", $id);
                }

                if (is_array($attributes)) {
                    if (is_string($attributes['theme'] ?? null) && $attributes['theme'] !== '') {
                        $add("theme attribute on {$name}", $id);
                    }

                    if (in_array($attributes['cardStyle'] ?? '', ['tinted', 'bold'], true)) {
                        $add("cardStyle {$attributes['cardStyle']}", $id);
                    }
                }
            }
        }

        // class tokens, read only where classes are written (class="..." in markup, "className":"..." in a block's attributes), so a sentence that says
        // "theme-less" is not counted
        preg_match_all('~(?:\bclass=["\']|"className":")([^"\']*)~', $content, $classLists);
        $tokens = preg_split('~\s+~', trim(implode(' ', $classLists[1])), -1, PREG_SPLIT_NO_EMPTY);
        $count = count(array_filter($tokens, fn($token) => preg_match('~^theme-[a-z][a-z0-9-]*$~', $token)));
        $count && $add('theme-* class', $id, $count);

        foreach (['tinted', 'bold'] as $style) {
            $count = count(array_filter($tokens, fn($token) => $token === "pkde-card-{$style}"));
            $count && $add("pkde-card-{$style} class", $id, $count);
        }
    }

    ksort($rows);

    return $rows;
}

/** The table as text: what, posts, uses, first ids. */
function content_usage_table(array $rows): string
{
    $width = max(array_map('strlen', array_keys($rows) ?: ['']));
    $out = str_pad('item', $width) . "  posts  uses  first post ids\n" . str_repeat('-', $width + 30) . "\n";

    foreach ($rows as $label => $row) {
        $out .= str_pad($label, $width) . '  ' . str_pad((string) count($row['posts']), 5, ' ', STR_PAD_LEFT) . '  ' . str_pad((string) $row['uses'], 4, ' ', STR_PAD_LEFT) . '  '
            . implode(',', array_slice($row['posts'], 0, 8)) . (count($row['posts']) > 8 ? ',...' : '') . "\n";
    }

    return $out;
}

if (! defined('ABSPATH')) {
    return; // loaded outside WordPress (the Pest tests): the functions above are all there is
}

(function () {
    global $wpdb;

    $statuses = ["'publish'", "'draft'", "'pending'", "'future'", "'private'"];
    $skip = ["'revision'", "'attachment'", "'nav_menu_item'", "'customize_changeset'", "'auto-draft'"];
    $reader = function () use ($wpdb, $statuses, $skip) {
        $last = 0;

        // 200 posts at a time by id, so a large site is read without holding it all in memory
        while ($batch = $wpdb->get_results(sprintf(
            "SELECT ID, post_content FROM {$wpdb->posts} WHERE ID > %d AND post_status IN (%s) AND post_type NOT IN (%s) AND post_content <> '' ORDER BY ID ASC LIMIT 200",
            $last,
            implode(',', $statuses),
            implode(',', $skip),
        ), ARRAY_A)) {
            foreach ($batch as $row) {
                yield $row;
                $last = (int) $row['ID'];
            }
        }
    };

    echo content_usage_table(content_usage($reader())), "\nRead-only: nothing was changed. Report this table.\n";
})();
