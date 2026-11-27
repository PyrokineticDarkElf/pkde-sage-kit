<?php

/**
 * The post's contents list has its final state at first paint, at every width: the markup is closed, CSS shows the list from 1024px, the script never closes it at load.
 */
it('prints the contents closed and shows the list on a wide screen by CSS, and the script never closes it at load', function () {
    $root = dirname(__DIR__, 2);
    $view = file_get_contents($root . '/resources/views/partials/toc.blade.php');
    $css = file_get_contents($root . '/resources/css/post-single.css');
    $js = kit_source($root, 'js');

    expect($view)->toContain('<details class="post-toc__details">')->not->toContain('post-toc__details" open')
        ->and($css)->toContain('.post-toc__details:not([open])::details-content')->toContain('content-visibility: visible')
        ->and($js)->toContain('if (wide.matches) tocDetails.open = true;')
        ->and($js)->not->toContain('tocDetails.open = wide.matches;\n}')
        ->and($js)->not->toContain('syncDetails();');
});
