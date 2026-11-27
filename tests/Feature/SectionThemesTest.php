<?php

/**
 * the Brand <Label> section themes as block styles, and the CSS that lets core blocks and the kit's blocks follow a section.
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
