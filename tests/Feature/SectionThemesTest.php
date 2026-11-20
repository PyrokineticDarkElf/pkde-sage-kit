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

    expect($css)->toContain('var(--section-bg)', 'var(--section-text)', 'var(--section-heading)', 'var(--section-link)', 'var(--section-button-bg)', 'var(--section-button-text)', 'var(--section-button-border)', 'var(--section-button-alt-text)', 'var(--section-border)', 'var(--section-icon)', 'var(--section-muted)')
        // nothing here may outrank the own colour of a block INSIDE a section (has-*-color classes carry !important; inline styles beat selectors);
        // the only !important is the Group or Section that has a Brand style AND its own background or text colour: the theme wins on that block
        ->and(substr_count(preg_replace('~/\*.*?\*/~s', '', $css), '!important'))->toBe(2)
        ->and(preg_replace('~/\*.*?\*/~s', '', $css))->toMatch('/\.has-background \{\s*background: var\(--section-bg\) !important;/')
        ->and(preg_replace('~/\*.*?\*/~s', '', $css))->toMatch('/\.has-text-color \{\s*color: var\(--section-text\) !important;/')
        // kit blocks that colour themselves, and blocks with their own link colour, are left alone
        ->and($css)->toContain('.pkde-section', 'article', '.has-link-color', '[class*="wp-elements-"]')
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./section-themes.css";');
});

it('puts a section\'s descendant rules one level above a site\'s generic .page-content colour rules, and makes Outline a true outline', function () use ($root) {
    $css = preg_replace('~/\*.*?\*/~s', '', file_get_contents("$root/resources/css/section-themes.css"));

    // every descendant rule repeats the style attribute (0,3,x beats .page-content h2.wp-block-heading at 0,2,1); only the paint rule on the section itself is single
    expect(substr_count($css, '[class*="is-style-brand-"][class*="is-style-brand-"] '))->toBeGreaterThanOrEqual(7)
        ->and($css)->toContain('[class*="is-style-brand-"] {') // the paint rule
        ->and($css)->toContain(':is(p, li, dt, dd, td, th, blockquote, cite):not([class*="pkde-"])') // running text: a site's .page-content p must not beat inheriting the theme's text colour
        ->and($css)->toMatch('/\.is-style-outline > \.wp-block-button__link[^{]*\{[^}]*background-color: transparent;[^}]*box-shadow: none;/s')
        ->and($css)->toMatch('/box-shadow: inset 0 0 0 2px var\(--section-button-border\)/');

    $blocks = file_get_contents("$root/resources/css/sections.css");

    expect($blocks)->toContain('.pkde-block.pkde-block :where(h1, h2, h3, h4)', '.pkde-block .pkde-cta-band__text', '.pkde-block .pkde-quote blockquote')
        ->and($blocks)->not->toMatch('/^\.pkde-(eyebrow|feature__text|quote__name|hero__text) \{/m'); // none of the new blocks' text colours is a single-class rule any more
});

it('gives the section header, accordion, gallery and carousel section-aware colours whose fallback is the colour they always had', function () use ($root) {
    foreach (['views/partials/blocks/section-header.blade.php', 'views/partials/blocks/accordion-layout.blade.php', 'blocks/gallery/render.php', 'blocks/carousel/render.php'] as $file) {
        $source = file_get_contents("$root/resources/$file");

        expect($source)->toContain('var(--section-')
            ->and($source)->not->toMatch('/[\' "]text-neutral-(950|600)[\' "]/'); // no bare heading or description colour left
    }

    expect(file_get_contents("$root/resources/blocks/gallery/render.php"))->toContain('var(--section-heading,var(--color-neutral-950))', 'var(--section-muted,var(--color-neutral-600))');
});
