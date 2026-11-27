<?php

/**
 * (prototype): the section blocks (hero layouts, text and image, feature grid, testimonials, CTA image slot, the global CTA band), the
 * declaration file of the global blocks, the icon set, the patterns and the About starter. No attribute of an existing block is removed or changed.
 */
$root = dirname(__DIR__, 2);
$block = fn(string $name) => json_decode(file_get_contents(dirname(__DIR__, 2) . "/resources/blocks/$name/block.json"), true);

it('keeps every attribute the hero and the CTA already had, with the same defaults, and adds the new ones beside them', function () use ($block) {
    $hero = $block('hero')['attributes'];

    expect($hero['heading']['default'])->toBe('Your heading here')
        ->and($hero['content']['default'])->toBe('A short supporting sentence goes here.')
        ->and(array_keys($hero))->toContain('buttonText', 'buttonUrl', 'imageUrl', 'imageId', 'imageAlt', 'theme')
        ->and($hero['layout']['default'])->toBe('overlay') // the full-width image with its overlay stays the default
        ->and($hero['imageSide']['default'])->toBe('right')
        ->and($hero['imageFit']['default'])->toBe('cover') // a hero saved before the option crops exactly as before
        ->and($hero['headingLevel']['default'])->toBe(2);

    $cta = $block('cta')['attributes'];

    expect(array_keys($cta))->toContain('style', 'theme', 'heading', 'content', 'primaryButtonText', 'primaryButtonUrl', 'secondaryButtonText', 'secondaryButtonUrl', 'imageUrl', 'imageId', 'imageAlt')
        ->and($cta['style']['default'])->toBe('section');
});

it('marks what a client may edit as content, so a locked pattern still lets them write and replace pictures', function () use ($block) {
    foreach (['hero' => ['heading', 'content', 'buttonText', 'buttonUrl', 'imageUrl', 'eyebrow'], 'text-and-image' => ['imageUrl', 'imageAlt', 'eyebrow'], 'feature-grid' => ['heading', 'intro', 'eyebrow'], 'feature-item' => ['heading', 'text', 'linkUrl', 'icon'], 'quotes' => ['heading', 'items'], 'cta' => ['heading', 'content', 'imageUrl']] as $name => $attributes) {
        foreach ($attributes as $attribute) {
            expect($block($name)['attributes'][$attribute]['__experimentalRole'] ?? null)->toBe('content', "$name.$attribute");
        }
    }

    // structure and look settings are NOT content: a locked section cannot change them
    expect($block('hero')['attributes']['layout']['__experimentalRole'] ?? null)->toBeNull()
        ->and($block('feature-grid')['attributes']['columns']['__experimentalRole'] ?? null)->toBeNull();
});

it('registers the new blocks with the names the brief gave, a feature item only inside a feature grid, and a testimonials block that cannot collide with el\'s', function () use ($block) {
    expect($block('text-and-image')['name'])->toBe('sage/text-and-image')
        ->and($block('feature-grid')['name'])->toBe('sage/feature-grid')
        ->and($block('feature-item')['parent'])->toBe(['sage/feature-grid'])
        ->and($block('quotes')['name'])->toBe('sage/quotes')->not->toBe('sage/testimonials')
        ->and($block('cta-band')['name'])->toBe('sage/cta-band')
        ->and(array_keys($block('cta-band')['attributes']))->toBe(['align']) // a global block: no editable field on the page, only its alignment
        ->and($block('text-and-image')['attributes']['templateLock']['enum'])->toContain('contentOnly')
        ->and($block('feature-grid')['attributes']['templateLock']['enum'])->toContain('contentOnly')
        ->and($block('section')['attributes']['templateLock']['enum'])->toContain('contentOnly');
});

it('reads a global block\'s content from the declaration file and says nothing when it is missing', function () {
    $file = sys_get_temp_dir() . '/pkde-sections-' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($file, "<?php\nreturn ['cta-band' => ['text' => 'We plant a tree with every order.', 'theme' => 'brand-slate'], 'broken' => 'x'];\n");

    expect(\App\section_declaration('cta-band', $file))->toBe(['text' => 'We plant a tree with every order.', 'theme' => 'brand-slate'])
        ->and(\App\section_declaration('contact-strip', $file))->toBe([])
        ->and(\App\section_declaration('broken', $file))->toBe([])
        ->and(\App\section_declaration('cta-band', '/no/such/sections.php'))->toBe([]);

    unlink($file);
});

it('draws feature icons inline in currentColor, decorative to assistive technology, and nothing for an unknown key', function () {
    $svg = \App\feature_icon('heart');

    expect($svg)->toStartWith('<svg')->toContain('stroke="currentColor"', 'aria-hidden="true"', 'class="pkde-icon"')
        ->and(\App\feature_icon('no-such-icon'))->toBe('')
        ->and(array_keys(\App\feature_icons()))->toContain('check', 'leaf', 'heart', 'star', 'shield', 'truck', 'clock', 'phone', 'mail', 'map-pin', 'gift', 'sparkles');
});

it('lists typed-in quotes with the count limit and drops empty ones', function () {
    $quotes = \App\quotes_for(['source' => 'manual', 'count' => 2, 'items' => [['quote' => 'One', 'name' => 'A', 'rating' => 9], ['quote' => '  '], ['quote' => 'Two', 'rating' => 3], ['quote' => 'Three']]]);

    expect($quotes)->toHaveCount(2)
        ->and($quotes[0])->toBe(['quote' => 'One', 'name' => 'A', 'role' => '', 'rating' => 5]) // a rating is held to 0 to 5
        ->and($quotes[1]['quote'])->toBe('Two');
});

it('finds the section theme of a role and builds a Section locked to its content', function () {
    $themes = [['name' => 'brand-sage', 'mode' => 'solid', 'role' => 'brand'], ['name' => 'brand-sage-light', 'mode' => 'light', 'role' => 'brand'], ['name' => 'brand-slate', 'mode' => 'solid', 'role' => null]];

    expect(\App\section_theme_for('brand', 'light', $themes))->toBe('brand-sage-light')
        ->and(\App\section_theme_for('brand', 'solid', $themes))->toBe('brand-sage')
        ->and(\App\section_theme_for('accent', 'solid', $themes))->toBe('');

    $markup = \App\pattern_section('brand', 'light', '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->', $themes);

    expect($markup)->toContain('<!-- wp:sage/section {"className":"is-style-brand-sage-light","templateLock":"contentOnly"} -->', '<div class="wp-block-sage-section alignfull is-style-brand-sage-light">', '<!-- /wp:sage/section -->')
        ->and(\App\pattern_section('accent', 'solid', 'x', $themes))->toContain('<!-- wp:sage/section {"templateLock":"contentOnly"} -->'); // no theme on this palette: still locked
});

it('ships a locked pattern for every prototype block and an About page starter', function () use ($root) {
    $files = glob("$root/resources/patterns/*.php");
    $by = fn(string $slug) => array_values(array_filter($files, fn($file) => str_contains(file_get_contents($file), "Slug: $slug")))[0] ?? null;

    foreach (['pkde/hero-split' => 'sage/hero', 'pkde/text-and-image' => 'sage/text-and-image', 'pkde/features-cards' => 'sage/feature-grid', 'pkde/testimonials-cards' => 'sage/quotes', 'pkde/cta-split' => 'sage/cta', 'pkde/page-about' => 'sage/text-and-image'] as $slug => $block) {
        $file = $by($slug);
        expect($file)->not->toBeNull($slug);
        $source = file_get_contents($file);

        expect($source)->toContain("wp:$block")->toContain('\App\pattern_section(')->toMatch('/Title: .+/')->toMatch('/Categories: pkde/');
    }

    $about = file_get_contents($by('pkde/page-about'));

    expect($about)->toContain('Block Types: core/post-content', 'Post Types: page', 'Categories: pkde-pages')
        ->and(file_get_contents("$root/composer.json"))->toContain('app/patterns.php');
});

it('prints the global CTA band from the declaration only, with no copy of its content in the page', function () use ($root) {
    $render = file_get_contents("$root/resources/blocks/cta-band/render.php");

    expect($render)->toContain("\\App\\section_declaration('cta-band')", 'pkde-themed', 'alignfull', 'is-style-')
        ->and($render)->not->toContain('$attributes')
        ->and(file_get_contents("$root/resources/views/partials/blocks/cta-band.blade.php"))->toContain('--section')->not->toContain('<input');
});

it('turns a block\'s Card colour and Card button colour into a class and a style, and ignores anything that is not in the generated lists', function () use ($root) {
    $themes = [['name' => 'brand-sage'], ['name' => 'brand-sage-light']];
    $buttons = ['sage-600', 'blush-600', 'white'];

    expect(\App\card_wrapper_args([], 'pkde-block', '', $themes, $buttons))->toBe(['class' => 'pkde-block', 'style' => '']) // Auto: nothing added
        ->and(\App\card_wrapper_args(['cardTheme' => 'brand-sage'], 'x', '', $themes, $buttons)['class'])->toBe('x has-card-theme-brand-sage')
        ->and(\App\card_wrapper_args(['cardTheme' => 'brand-evil"><script>'], '', '', $themes, $buttons)['class'])->toBe('') // not a theme of this palette
        ->and(\App\card_wrapper_args(['cardButton' => 'sage-600'], '', '--a: 1;', $themes, $buttons)['style'])->toBe('--a: 1; --section-card-button-bg: var(--color-sage-600); --section-card-button-text: var(--color-sage-600-fg); --section-card-button-border: var(--color-sage-600);')
        ->and(\App\card_wrapper_args(['cardButton' => 'white'], '', '', $themes, $buttons)['style'])->toContain('var(--color-white-exact)', 'var(--color-black-exact)')
        ->and(\App\card_wrapper_args(['cardButton' => 'red; background: url(x)'], '', '', $themes, $buttons)['style'])->toBe('')
        ->and(\App\card_wrapper_args(['cardButton' => 'stone-600'], '', '', $themes, $buttons)['style'])->toBe(''); // not in the Button list

    foreach (['quotes', 'feature-grid', 'product-loop', 'product-categories', 'entry-grid', 'latest-posts', 'featured-posts'] as $block) {
        $json = json_decode(file_get_contents("$root/resources/blocks/$block/block.json"), true);

        expect($json['attributes']['cardTheme']['default'])->toBe('', $block)->and($json['attributes']['cardButton']['default'])->toBe('', $block)
            ->and(file_get_contents("$root/resources/blocks/$block/render.php"))->toContain('card_wrapper_args(')
            ->and(file_get_contents("$root/resources/blocks/$block/edit.jsx"))->toContain('CardColourPanel');
    }
});
