<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Retrieve the site name.
     */
    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }

    /**
     * The palette colour whose section theme the site header wears (config/brand-palette.json's
     * `header.theme`, written by bin/generate-theme-tokens.mjs), or null for the default light header.
     */
    public function headerTheme(): ?string
    {
        $file = get_theme_file_path('resources/generated/header-options.php');

        return is_file($file) ? (include $file)['theme'] ?? null : null;
    }
}
