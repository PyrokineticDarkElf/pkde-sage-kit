<?php

/**
 * The kit's motion system (WP-103): the guard rails of the reveal, the site switch, the tokens, the hover and reduced-motion gates, and no layout-animated or transition-all
 * left in the kit. The behaviour in a browser (LCP, CLS, the reveal, touch, the editor roles) is measured on the template stack; the report says so.
 */
use function App\motion_add_html_class;
use function App\motion_classes;
use function App\motion_html_class;
use function App\motion_mode_of;

$root = dirname(__DIR__, 2);
$read = fn(string $file) => file_get_contents("$root/$file");
$strip = fn(string $css) => preg_replace('~/\*.*?\*/~s', '', $css);

it('gives the reveal and the lift to a block that has them set, and keeps older saved settings rendering', function () {
    $attrs = ['animationHover' => 'lift', 'animationEntrance' => 'fade-up'];

    expect(motion_classes($attrs, 'sage/section', false, 'on'))->toBe(['pkde-hover-lift', 'pkde-reveal'])
        ->and(motion_classes($attrs, 'core/paragraph', false, 'on'))->toBe(['pkde-hover-lift', 'pkde-reveal']) // small blocks keep rendering
        ->and(motion_classes([], 'sage/section', false, 'on'))->toBe([])
        ->and(motion_classes(['animationEntrance' => ''], 'sage/section', false, 'on'))->toBe([]); // an empty value is "off"
});

it('never reveals the hero, a slider or a slide, and never the first block of the page', function () {
    $attrs = ['animationEntrance' => 'fade-up', 'animationHover' => 'lift'];

    foreach (['sage/hero', 'sage/hero-slider', 'sage/hero-slide'] as $name) {
        expect(motion_classes($attrs, $name, false, 'on'))->toBe(['pkde-hover-lift']); // the lift is a hover, not a load effect
    }

    expect(motion_classes($attrs, 'sage/section', true, 'on'))->toBe(['pkde-hover-lift']);
});

it('follows the site switch: reduced has no reveal, off has no movement at all, and an unknown value means on', function () {
    $attrs = ['animationEntrance' => 'fade-up', 'animationHover' => 'lift'];

    expect(motion_classes($attrs, 'sage/section', false, 'reduced'))->toBe(['pkde-hover-lift'])
        ->and(motion_classes($attrs, 'sage/section', false, 'off'))->toBe([])
        ->and(motion_mode_of('reduced'))->toBe('reduced')
        ->and(motion_mode_of('nonsense'))->toBe('on')
        ->and(motion_mode_of(null))->toBe('on')
        ->and(motion_html_class('off'))->toBe('pkde-motion-off')
        ->and(motion_html_class('x'))->toBe('pkde-motion-on');
});

it('puts the mode on <html> from config, whether or not the tag already has a class', function () {
    expect(motion_add_html_class('lang="en-US"', 'pkde-motion-on'))->toBe('lang="en-US" class="pkde-motion-on"')
        ->and(motion_add_html_class('lang="en-US" class="a b"', 'pkde-motion-off'))->toBe('lang="en-US" class="a b pkde-motion-off"');
});

it('hides a revealing block only under js-motion, never one with the LCP image, only on screen and for visitors who accept motion, with opacity and translate only', function () use ($read, $strip) {
    $css = $strip($read('resources/css/motion.css'));

    expect($css)->toContain('html.js-motion:not(.pkde-motion-off, .pkde-motion-reduced) .pkde-reveal:not(.is-revealed, :has(img[fetchpriority="high"]))')
        ->and($css)->toContain('@media screen and (prefers-reduced-motion: no-preference)')
        ->and($css)->toContain('translate: 0 var(--pkde-reveal-distance)')
        ->and($css)->not->toContain('animation-timeline') // the scroll-linked version is retired
        ->and($css)->not->toContain('transition: all')
        ->and($read('resources/css/kit.css'))->not->toContain('animation-timeline')
        ->and($read('resources/css/kit.css'))->toContain('@import "./motion.css";');

    // the distance is at most 12px and the reveal runs on the slow token (400ms)
    expect($css)->toContain('--pkde-reveal-distance: 12px', '--pkde-motion-slow: 400ms', '--pkde-zoom-duration: 300ms', '--pkde-motion-fast: 150ms', '--pkde-motion-base: 250ms');
});

it('moves things on hover only for a pointer that can hover and a visitor who accepts motion, and presses with transform only', function () use ($read, $strip) {
    $css = $strip($read('resources/css/motion.css'));

    expect($css)->toContain('@media (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)')
        ->and($css)->toContain('.group:is(:hover, :focus-within) .pkde-zoom')
        ->and($css)->toContain('transform: scale(var(--pkde-zoom-scale))')
        ->and($css)->toContain('transform: scale(var(--pkde-press))')
        ->and($css)->toContain(':not(.checkout-button, #place_order, .wc-block-components-checkout-place-order-button)')
        ->and($css)->toContain('interpolate-size: allow-keywords')
        ->and($css)->toContain('::details-content');

    // the smooth accordion and the press are inside the reduced-motion gate
    expect(preg_match('~@media \(prefers-reduced-motion: no-preference\) \{\s*html:not\(\.pkde-motion-off\) :is\(\.wp-block-sage-accordion-item~', $css))->toBe(1);
});

it('has one shared card zoom, no per-view utilities, no transition-all, and dots that animate transform', function () use ($root, $read) {
    $views = array_merge(glob("$root/resources/views/**/*.php") ?: [], glob("$root/resources/views/*/*/*.php") ?: [], glob("$root/resources/blocks/*/render.php") ?: [], [$root . '/app/faq.php']);
    $sources = implode("\n", array_map('file_get_contents', array_merge($views, glob("$root/resources/views/*.php") ?: [])));

    foreach (['resources/views/partials/content.blade.php', 'resources/views/woocommerce/content-product.blade.php', 'resources/views/partials/related-post-card.blade.php', 'resources/views/partials/blocks/category-cards.blade.php', 'resources/views/partials/blocks/brand-cards.blade.php'] as $file) {
        expect($read($file))->toContain('pkde-zoom')->not->toContain('group-hover:scale')->not->toContain('duration-500');
    }

    expect($sources)->not->toContain('transition-all');

    foreach (['resources/blocks/carousel/render.php', 'resources/blocks/hero-slider/render.php'] as $file) {
        // the active dot is a pill drawn by clip-path (motion.css .pkde-dot), never a scaled circle and never a width transition (TPL-287)
        expect($read($file))->not->toContain('transition-all')->not->toContain('scale-x')->not->toContain('group-data-[active]:w-5')->toContain('pkde-dot');
    }

    expect($read('resources/css/sections.css'))->not->toContain('scaleX')->not->toContain('transition: width')
        ->and($read('resources/css/motion.css'))->toContain('.pkde-dot {')->toContain('clip-path: inset(0 0.375rem round')->toContain('[data-active] > .pkde-dot')->not->toContain('scaleX')
        ->and($read('resources/views/partials/blocks/quotes.blade.php'))->toContain('<span class="pkde-dot"></span>');
});

it('has no literal duration left in the kit transitions: they read the tokens (a tile may still override its own)', function () use ($root, $strip) {
    foreach (glob("$root/resources/css/*.css") as $file) {
        $css = $strip(file_get_contents($file));
        preg_match_all('/^\s*transition:\s*([^;]+);/m', $css, $m);

        foreach ($m[1] as $value) {
            if (trim($value) === 'none') {
                continue;
            }

            expect(str_contains($value, 'var(--pkde-'))->toBeTrue(basename($file) . ': ' . $value);
        }
    }
});

it('makes the slider jump under reduced motion, and ships a reveal script that needs no library and stays small', function () use ($read) {
    $carousel = $read('resources/js/carousel.js');
    $motion = $read('resources/js/motion.js');

    expect($carousel)->toContain('scrollPrev(prefersReducedMotion)', 'scrollNext(prefersReducedMotion)', 'scrollTo(index, prefersReducedMotion)')
        ->and($motion)->toContain('IntersectionObserver', "classList.add('js-motion')", "classList.add('is-revealed')", 'prefers-reduced-motion: reduce', 'pkde-motion-(off|reduced)')
        ->and($motion)->not->toContain('import ')
        ->and(strlen(preg_replace('~//.*|\s+~', ' ', $motion)))->toBeLessThan(1400)
        ->and($read('resources/js/kit.js'))->toContain("import './motion.js';");
});

it('gives Administrators the full panel and everyone else one Reveal on scroll toggle on Section and the kit section blocks, only when the switch is on', function () use ($read) {
    $jsx = $read('resources/js/animation.jsx');
    $php = $read('app/motion.php');

    expect($jsx)->toContain('Reveal on scroll', 'pkdeMotionPanel', 'pkdeMotionBlocks', "mode !== 'on'", "dataSelect('core/editor')", "block.name === 'sage/section'", "animationEntrance === undefined")
        ->and($php)->toContain("'pkdeMotionPanel'", 'manage_options', "'sage/section'", "'sage/faq'", 'pkde_kit/motion', "'language_attributes'", "'pre_render_block'")
        ->and($read('composer.json'))->toContain('app/motion.php');
});
