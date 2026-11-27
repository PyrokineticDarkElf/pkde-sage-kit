<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

/** kit.css or kit.js together with the shop file that goes beside it (kit-shop.css, kit-shop.js): for a test that looks for a rule or an import in either. */
function kit_source(string $root, string $ext): string
{
    $dir = $ext === 'css' ? 'css' : 'js';

    return file_get_contents("$root/resources/$dir/kit.$ext") . "\n" . file_get_contents("$root/resources/$dir/kit-shop.$ext");
}
