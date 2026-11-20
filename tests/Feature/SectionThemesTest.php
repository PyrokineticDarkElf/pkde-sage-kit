<?php

/**
 * WP-100 phase 1: the Brand <Label> section themes as block styles, and the CSS that lets core blocks and the kit's blocks follow a section.
 */
$root = dirname(__DIR__, 2);

it('reads the generated list of section themes and skips malformed rows', function () {
    $file = sys_get_temp_dir() . '/pkde-section-themes-' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($file, "<?php\nreturn [['name' => 'brand-sage', 'label' => 'Brand Sage', 'mode' => 'solid', 'colour' => 'sage'], ['name' => '', 'label' => 'x'], 'junk', ['name' => 'brand-sage-light', 'label' => 'Brand Sage (light)']];\n");

    expect(\App\section_themes($file))->toBe([
        ['name' => 'brand-sage', 'label' => 'Brand Sage', 'mode' => 'solid', 'colour' => 'sage'],
        ['name' => 'brand-sage-light', 'label' => 'Brand Sage (light)'],
    ])->and(\App\section_themes('/no/such/file.php'))->toBe([]);

    unlink($file);
});

it('registers the themes as styles of the Group and Section blocks on init', function () use ($root) {
    $php = file_get_contents("$root/app/section-themes.php");

    expect($php)->toContain("['core/group', 'sage/section']", 'register_block_style($block', "add_action('init'", "apply_filters('pkde_kit/section_theme_blocks'")
        ->and(file_get_contents("$root/composer.json"))->toContain('app/section-themes.php');
});

it('paints a themed section from its roles and lets core blocks follow it, below a block\'s own colour choice', function () use ($root) {
    $css = file_get_contents("$root/resources/css/section-themes.css");

    expect($css)->toContain('var(--section-bg)', 'var(--section-text)', 'var(--section-heading)', 'var(--section-link)', 'var(--section-button-bg)', 'var(--section-button-text)', 'var(--section-button-alt-bg)', 'var(--section-button-alt-text)', 'var(--section-border)', 'var(--section-icon)', 'var(--section-muted)')
        // nothing here may outrank a block's own colour (has-*-color classes carry !important; inline styles beat selectors)
        ->and(preg_replace('~/\*.*?\*/~s', '', $css))->not->toContain('!important')
        // kit blocks that colour themselves, and blocks with their own link colour, are left alone
        ->and($css)->toContain('.pkde-section', 'article', '.has-link-color', '[class*="wp-elements-"]')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./section-themes.css";');
});

it('gives the section header, accordion, gallery and carousel section-aware colours whose fallback is the colour they always had', function () use ($root) {
    foreach (['views/partials/blocks/section-header.blade.php', 'views/partials/blocks/accordion-layout.blade.php', 'blocks/gallery/render.php', 'blocks/carousel/render.php'] as $file) {
        $source = file_get_contents("$root/resources/$file");

        expect($source)->toContain('var(--section-')
            ->and($source)->not->toMatch('/[\' "]text-neutral-(950|600)[\' "]/'); // no bare heading or description colour left
    }

    expect(file_get_contents("$root/resources/blocks/gallery/render.php"))->toContain('var(--section-heading,var(--color-neutral-950))', 'var(--section-muted,var(--color-neutral-600))');
});
