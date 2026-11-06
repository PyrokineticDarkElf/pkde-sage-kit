<?php

/**
 * Keeping a page out of the page cache when it carries something personal (MGR-76). The page cache may now serve visitors who have a basket, so a
 * page generated for ONE visitor must never be stored for the next: every personal bit is filled in by the browser, and the one thing the server
 * still prints for a visitor, a pending WooCommerce notice, marks its page uncacheable. A site that prints anything else per visitor calls
 * \App\no_page_cache() in the same request. See docs/page-cache.md.
 */

namespace App;

/** Marks this response as not cacheable: DONOTCACHEPAGE (W3 Total Cache, WP Super Cache, WP Rocket ... honour it) and no-cache headers. */
function no_page_cache(): void
{
    if (! defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    if (function_exists('nocache_headers') && ! headers_sent()) {
        nocache_headers();
    }
}

/** Prints WooCommerce's pending notices for this visitor, and keeps the page they appear on out of the page cache. */
function print_notices_uncached(): void
{
    if (function_exists('wc_notice_count') && wc_notice_count() > 0) {
        no_page_cache();
    }

    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }
}
