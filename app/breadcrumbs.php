<?php

/**
 * Breadcrumbs the KIT owns (owner via the manager, MGR-77 parity): the same trail, separator and labels on every site, whatever its Rank Math
 * settings, and a trail when Rank Math's breadcrumb module is off.
 *
 *   - With Rank Math's breadcrumbs on, its trail is used (and its BreadcrumbList schema stays), but its settings and labels are forced to the
 *     standard through `rank_math/frontend/breadcrumb/settings` and `/strings`: the " / " separator, the "Home" label, the current page as the
 *     last crumb, ancestors and the blog page shown, no prefix. `pkde_kit/breadcrumb_standard` (array settings, array strings) may change it.
 *   - With them off (or Rank Math not installed) the kit builds the trail itself, in the same markup: <nav aria-label="breadcrumbs"
 *     class="rank-math-breadcrumb"><p><a>Home</a><span class="separator"> / </span> ... <span class="last">Title</span></p></nav>. WooCommerce pages
 *     take WooCommerce's own crumbs. No schema is printed for the fallback.
 *
 * Rank Math's own switch for the module, `Helper::is_breadcrumbs_enabled()`, is true when the theme declares support for 'rank-math-breadcrumbs', so the
 * kit declares it: the trail no longer depends on that setting in wp-admin either (`pkde_kit/force_rank_math_breadcrumbs` false leaves it to the site).
 *
 * The pure parts (the standard, the markup, the crumbs from a context array) need no WordPress, so a test covers them.
 */

namespace App;

/**
 * The settings and strings Rank Math is forced to.
 *
 * @return array{settings: array<string, mixed>, strings: array<string, string>}
 */
function breadcrumb_standard(): array
{
    $t = fn(string $text) => function_exists('__') ? __($text, 'sage') : $text;
    $standard = [
        'settings' => [
            'home' => true,
            'separator' => '/',
            'remove_title' => false,
            'hide_tax_name' => false,
            'show_ancestors' => true,
            'show_blog' => true,
        ],
        'strings' => [
            'prefix' => '',
            'home' => $t('Home'),
            'error404' => $t('404 Error: page not found'),
            'search_format' => $t('Results for %s'),
            'archive_format' => $t('Archives for %s'),
        ],
    ];

    return function_exists('apply_filters') ? (array) apply_filters('pkde_kit/breadcrumb_standard', $standard) : $standard;
}

/** The Rank Math trail's markup, for a list of [label, url] crumbs (the last one is the current page, not a link). */
function breadcrumb_markup(array $crumbs, string $separator = '/'): string
{
    $html = '';
    $last = count($crumbs) - 1;

    foreach ($crumbs as $i => [$label, $url]) {
        $html .= $url !== '' && $i !== $last
            ? '<a href="' . htmlspecialchars((string) $url, ENT_QUOTES) . '">' . htmlspecialchars((string) $label, ENT_QUOTES) . '</a>'
            : '<span class="last">' . htmlspecialchars((string) $label, ENT_QUOTES) . '</span>';

        if ($i !== $last) {
            $html .= '<span class="separator"> ' . htmlspecialchars($separator, ENT_QUOTES) . ' </span>';
        }
    }

    return $html === '' ? '' : '<nav aria-label="breadcrumbs" class="rank-math-breadcrumb"><p>' . $html . '</p></nav>';
}

/**
 * The fallback trail from a context: type (front, 404, search, home, post, page, term, author, date, archive), the home crumb, the blog page
 * ([label, url] or null), the object's own title, its ancestors ([label, url] list, outermost first) and the page number.
 *
 * @param  array<string, mixed>  $c
 * @return list<array{0: string, 1: string}>
 */
function breadcrumb_crumbs(array $c): array
{
    $type = $c['type'] ?? '';

    if ($type === 'front' || $type === '') {
        return [];
    }

    $crumbs = [$c['home'] ?? ['Home', '/']];
    $blog = $c['blog'] ?? null;

    switch ($type) {
        case '404':
        case 'search':
            $crumbs[] = [(string) ($c['title'] ?? ''), ''];
            break;
        case 'home': // the posts page itself
            $crumbs[] = [(string) ($c['title'] ?? ''), ''];
            break;
        case 'post':
        case 'term':
        case 'author':
        case 'date':
            if ($blog && ($c['with_blog'] ?? true)) {
                $crumbs[] = $blog;
            }
            // no break
        default: // page, archive and the rest
            foreach ((array) ($c['ancestors'] ?? []) as $ancestor) {
                $crumbs[] = $ancestor;
            }
            $crumbs[] = [(string) ($c['title'] ?? ''), ''];
    }

    if (($c['page'] ?? 1) > 1) {
        $last = array_pop($crumbs);
        $crumbs[] = [$last[0], (string) ($c['url'] ?? '#')]; // the title becomes a link to page one
        $crumbs[] = [sprintf($c['page_label'] ?? 'Page %d', $c['page']), ''];
    }

    return array_values(array_filter($crumbs, fn($crumb) => $crumb[0] !== ''));
}

/** The context for breadcrumb_crumbs() from the current request. */
function breadcrumb_context(): array
{
    $home = [function_exists('__') ? __('Home', 'sage') : 'Home', home_url('/')];
    $posts = (int) get_option('page_for_posts');
    $blog = $posts && get_option('show_on_front') === 'page' ? [get_the_title($posts), get_permalink($posts)] : null;
    $page = max(1, (int) get_query_var('paged'));
    $c = ['home' => $home, 'blog' => $blog, 'page' => $page, 'page_label' => __('Page %d', 'sage'), 'url' => ''];

    $termTrail = function (\WP_Term $term) {
        $trail = [];
        foreach (array_reverse(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy')) as $id) {
            $parent = get_term($id, $term->taxonomy);
            if ($parent instanceof \WP_Term) {
                $trail[] = [$parent->name, (string) get_term_link($parent)];
            }
        }

        return $trail;
    };

    if (is_front_page() && ! is_paged()) {
        return $c + ['type' => 'front'];
    }
    if (is_404()) {
        return $c + ['type' => '404', 'title' => __('404 Error: page not found', 'sage')];
    }
    if (is_search()) {
        return $c + ['type' => 'search', 'title' => sprintf(__('Results for %s', 'sage'), get_search_query())];
    }
    if (is_home()) {
        return $c + ['type' => 'home', 'title' => $posts ? get_the_title($posts) : __('Latest Posts', 'sage'), 'url' => $posts ? (string) get_permalink($posts) : home_url('/')];
    }
    if (is_singular()) {
        $post = get_queried_object();
        $ancestors = [];
        if ($post instanceof \WP_Post && is_post_type_hierarchical($post->post_type)) {
            foreach (array_reverse(get_post_ancestors($post)) as $id) {
                $ancestors[] = [get_the_title($id), (string) get_permalink($id)];
            }
        } elseif ($post instanceof \WP_Post && $post->post_type === 'post') {
            $cats = get_the_category($post->ID);
            if ($cats) {
                $ancestors = array_merge($termTrail($cats[0]), [[$cats[0]->name, (string) get_term_link($cats[0])]]);
            }
        }

        return $c + ['type' => $post && $post->post_type === 'post' ? 'post' : 'page', 'title' => get_the_title(), 'ancestors' => $ancestors, 'url' => (string) get_permalink()];
    }
    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();

        return $term instanceof \WP_Term
            ? $c + ['type' => 'term', 'title' => $term->name, 'ancestors' => $termTrail($term), 'url' => (string) get_term_link($term), 'with_blog' => is_category() || is_tag()]
            : $c + ['type' => '', 'title' => ''];
    }
    if (is_author()) {
        return $c + ['type' => 'author', 'title' => sprintf(__('Author: %s', 'sage'), get_the_author_meta('display_name', (int) get_queried_object_id()))];
    }
    if (is_date()) {
        return $c + ['type' => 'date', 'title' => wp_strip_all_tags(get_the_archive_title())];
    }
    if (is_post_type_archive()) {
        return $c + ['type' => 'archive', 'title' => post_type_archive_title('', false), 'url' => (string) get_post_type_archive_link(get_query_var('post_type'))];
    }

    return $c + ['type' => '', 'title' => ''];
}

/**
 * The trail for this request: Rank Math's (standardised) when its breadcrumbs are on, else WooCommerce's crumbs on shop pages, else the kit's.
 * '' where there is none (the front page). `pkde_kit/breadcrumb_html` may replace it.
 */
function breadcrumb_trail_html(): string
{
    $html = function_exists('rank_math_get_breadcrumbs') ? trim((string) rank_math_get_breadcrumbs()) : '';

    if ($html === '') {
        $crumbs = [];
        if (function_exists('is_woocommerce') && (is_woocommerce() || is_product_taxonomy()) && class_exists('WC_Breadcrumb')) {
            $crumbs = array_map(fn($crumb) => [wp_strip_all_tags((string) $crumb[0]), (string) ($crumb[1] ?? '')], (new \WC_Breadcrumb())->generate());
            $last = count($crumbs) - 1;
            if ($last >= 0) {
                $crumbs[$last][1] = '';
            }
        }
        $html = breadcrumb_markup($crumbs ?: breadcrumb_crumbs(breadcrumb_context()), breadcrumb_standard()['settings']['separator']);
    }

    return (string) apply_filters('pkde_kit/breadcrumb_html', $html);
}

if (! function_exists('add_filter')) {
    return;
}

add_action('after_setup_theme', function () {
    if (apply_filters('pkde_kit/force_rank_math_breadcrumbs', true)) {
        add_theme_support('rank-math-breadcrumbs');
    }
});

// Rank Math builds its trail from these (the filter's result wins over the site's own settings, see Breadcrumbs::__construct()).
add_filter('rank_math/frontend/breadcrumb/settings', fn($settings) => array_merge((array) $settings, breadcrumb_standard()['settings']));
add_filter('rank_math/frontend/breadcrumb/strings', fn($strings) => array_merge((array) $strings, breadcrumb_standard()['strings']));
