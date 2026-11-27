<?php

/**
 * The block editor canvas gets the kit's block styles: a theme's editor.css imports resources/css/editor-kit.css, which is kit.css whole plus the
 * editor page width. Until then the product loop's server-rendered list was a plain stack of cards in the canvas.
 */
$root = dirname(__DIR__, 2);

it('ships one editor stylesheet that holds kit.css and the editor page width', function () use ($root) {
    $css = file_get_contents("$root/resources/css/editor-kit.css");

    expect($css)->toContain('@import "./kit.css";')
        ->and($css)->toContain('@import "./editor-width.css";');
});

it('keeps the product loop grid rules in kit.css, which the editor stylesheet imports', function () use ($root) {
    $kit = file_get_contents("$root/resources/css/kit.css");

    expect($kit)->toContain('.woocommerce ul.products')
        ->and($kit)->toContain('.woocommerce.columns-4 ul.products');
});
