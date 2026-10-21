<?php

/*
| Every user-facing string of the kit goes through gettext with the one text domain `sage`, so a site changes the wording (a plain-
| punctuation house style, say) with a `gettext` filter or a translation file, and never has to override markup. A call with another
| domain would be a string a site cannot reach.
*/

it('uses only the sage text domain in PHP, views and editor components', function () {
    $root = dirname(__DIR__, 2);
    $files = [];
    foreach (['app', 'resources/views', 'resources/blocks', 'resources/js', 'src'] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", FilesystemIterator::SKIP_DOTS)) as $file) {
            if (preg_match('/\.(php|jsx?|json)$/', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
    }

    $other = [];
    foreach ($files as $file) {
        if (preg_match_all("/\b(?:__|_x|_n|_nx|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\((?:[^()]|\([^()]*\))*?,\s*'([a-z0-9_-]+)'\s*\)/i", file_get_contents($file), $m)) {
            foreach (array_unique($m[1]) as $domain) {
                if ($domain !== 'sage') {
                    $other[] = $domain . ' in ' . substr($file, strlen($root) + 1);
                }
            }
        }
    }

    expect($other)->toBe([]);
});

it('gives every block a textdomain so its title and description translate', function () {
    foreach (glob(dirname(__DIR__, 2) . '/resources/blocks/*/block.json') as $json) {
        expect(json_decode(file_get_contents($json), true)['textdomain'] ?? null)->toBe('sage', basename(dirname($json)));
    }
});
