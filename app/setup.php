<?php

/**
 * Theme setup.
 */

namespace App;

// Loaded by Composer's autoloader (autoload.files), also outside WordPress (the kit's own tests, Composer scripts): only the pure
// functions of helpers.php are wanted there.
if (! function_exists('add_action')) {
    return;
}

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    // Lets resources/js/editor.js give the canvas the front page's wrapper class (editor parity, docs/native-blocks.md).
    $settings['frontPageId'] = (int) get_option('page_on_front');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    // The theme's font faces are printed in the page head by the @fonts directive on the front end; the editor canvas
    // needs the same @font-face rules, or the editor shows fallback fonts that differ from the page.
    if (preg_match_all('~<style[^>]*>(.*?)</style>~s', \Illuminate\Support\Facades\Blade::render('@fonts'), $fonts)) {
        foreach ($fonts[1] as $css) {
            $settings['styles'][] = ['css' => $css];
        }
    }

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            if (! wp_script_is($dependency)) {
                wp_enqueue_script($dependency);
            }
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/** Add lightweight editorial status cues to post-list tables only. */
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();

    if (! $screen || $screen->base !== 'edit') {
        return;
    }

    // The kit's own stylesheet, served from where Composer installed it (inside the theme directory, vendor/pkde/sage-kit).
    $file = dirname(__DIR__) . '/resources/css/admin.css';
    $relativePath = ltrim(substr($file, strlen(get_template_directory())), '/');
    wp_enqueue_style(
        'sage-admin-list-tables',
        get_theme_file_uri($relativePath),
        [],
        (string) filemtime($file),
    );
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Block assets stay on demand (WordPress's default for classic themes since 6.9). Sage used to force
 * `should_load_separate_core_block_assets` to false, which loads every block's CSS and prints the block-supports CSS after the
 * footer: a layout shift of 0.36 to 0.42 on tcs, 0 once removed (TCS-87), and about 20 KB less wp-block-library.
 */

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * A logo uploaded in the Customizer (Site Identity), shown in the header in place of the site name text.
     * Flexible dimensions: the header scales it by height (sections/header.blade.php).
     *
     * @link https://developer.wordpress.org/themes/functionality/custom-logo/
     */
    add_theme_support('custom-logo', ['height' => 64, 'width' => 240, 'flex-height' => true, 'flex-width' => true]);

    /**
     * Every custom block in resources/blocks/ declares `"align": ["wide",
     * "full"]` support, but the editor only shows the Wide/Full toolbar
     * buttons at all once the theme opts in here too — see .alignwide/
     * .alignfull in app.css for the other half (making those classes
     * actually break out of a page's normal content column).
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#wide-alignment
     */
    add_theme_support('align-wide');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});

/**
 * Appearance > Site footer: when the footer is one synced pattern (a reusable block named "Site footer", shared by every
 * page) it is edited in one place. A classic theme has no template-part screen, so this opens the pattern's own editor.
 * Nothing is added when no such pattern exists, so a site without one sees no change.
 */
add_action('admin_menu', function () {
    $footer = get_posts(['post_type' => 'wp_block', 'name' => 'site-footer', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids'])[0] ?? null;

    if ($footer) {
        add_submenu_page('themes.php', __('Site footer', 'sage'), __('Site footer', 'sage'), 'edit_theme_options', 'post.php?post=' . (int) $footer . '&action=edit');
    }
});
