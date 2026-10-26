<?php

/*
| Every view the kit names must exist in the kit. A renamed or moved view that something still includes by its old name would
| otherwise fail only when that page renders.
*/

function kitRoot(): string
{
    return dirname(__DIR__, 2);
}

/** @return list<string> */
function kitSourceFiles(): array
{
    $files = [];
    foreach (['app', 'resources/views', 'resources/blocks', 'src'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(kitRoot() . "/$dir", FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (preg_match('/\.php$/', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

it('only includes kit views that exist', function () {
    $missing = [];
    foreach (kitSourceFiles() as $file) {
        $source = file_get_contents($file);
        preg_match_all("/['\"]pkde-kit::([a-z0-9._-]+)['\"]/i", $source, $names);
        foreach ($names[1] as $name) {
            if (! is_file(kitRoot() . '/resources/views/' . str_replace('.', '/', $name) . '.blade.php')) {
                $missing[] = "$name (in " . substr($file, strlen(kitRoot()) + 1) . ')';
            }
        }
        preg_match_all('/<x-pkde-kit::([a-z0-9._-]+)/i', $source, $components);
        foreach ($components[1] as $name) {
            if (! is_file(kitRoot() . '/resources/views/components/' . str_replace('.', '/', $name) . '.blade.php')) {
                $missing[] = "component $name (in " . substr($file, strlen(kitRoot()) + 1) . ')';
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

it('keeps no un-namespaced partial or component reference that would resolve only through the fallback (a dynamic prefix such as partials.content-<type> is deliberate)', function () {
    $legacy = [];
    foreach (kitSourceFiles() as $file) {
        if (str_ends_with($file, 'View/Composers/Post.php')) {
            continue; // lists the old names on purpose
        }
        preg_match_all("/['\"](partials\.[a-z0-9._-]*[a-z0-9])['\"]|<x-(placeholder-image|alert)\b/i", file_get_contents($file), $m);
        foreach (array_filter(array_merge($m[1], $m[2])) as $name) {
            $legacy[] = "$name in " . substr($file, strlen(kitRoot()) + 1);
        }
    }

    expect($legacy)->toBe([]);
});

it('marks the Sale and New badge links nofollow, so crawlers do not follow every card into the same filtered views', function () {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/woocommerce/content-product.blade.php');

    preg_match_all("/<a href=\"\{\{ add_query_arg\('sage_view', '(?:sale|new)'[^>]*>/", $view, $links);

    expect($links[0])->toHaveCount(2);
    foreach ($links[0] as $link) {
        expect($link)->toContain('rel="nofollow"');
    }
});

it('keeps amber text and white-on-amber markers at 700 or darker, the lightest step that clears 4.5:1 on a white or light card', function () {
    $offenders = [];
    foreach (glob(dirname(__DIR__, 2) . '/resources/views/{,*/,*/*/}*.blade.php', GLOB_BRACE) as $file) {
        if (preg_match('/\b(?:text|bg)-amber-(?:[1-6]00|50)\b/', file_get_contents($file))) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});
