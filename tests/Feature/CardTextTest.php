<?php

/**
 * The one server-side cap on card text (TPL-280): plain text, entities decoded once, at most 40 words (the filter changes it), no "Continued". WordPress is
 * stood in for by the few functions the helper calls.
 */
use function App\card_text;

if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string
    {
        return trim(strip_tags($text));
    }
}

if (! function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $num = 55, string $more = '…'): string
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return count($words) > $num ? implode(' ', array_slice($words, 0, $num)) . $more : implode(' ', $words);
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, $value, ...$args)
    {
        foreach ($GLOBALS['pkde_test_filters'][$hook] ?? [] as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }
}

it('strips tags and decodes entities once', function () {
    expect(card_text('<p>Tea &amp; coffee, &#039;quoted&#039;</p>'))->toBe("Tea & coffee, 'quoted'");
});

it('caps at 40 words with an ellipsis and leaves a shorter text whole', function () {
    $long = implode(' ', array_fill(0, 60, 'word'));
    expect(card_text($long))->toBe(implode(' ', array_fill(0, 40, 'word')) . '…')
        ->and(card_text('Short text.'))->toBe('Short text.')
        ->and(str_contains(card_text($long), 'Continued'))->toBeFalse();
});

it('takes the cap from the pkde_kit/card_text_words filter, and 0 means no cap', function () {
    $GLOBALS['pkde_test_filters']['pkde_kit/card_text_words'] = [fn() => 5];
    expect(card_text('one two three four five six seven'))->toBe('one two three four five…');
    $GLOBALS['pkde_test_filters']['pkde_kit/card_text_words'] = [fn() => 0];
    expect(card_text('one two three four five six seven'))->toBe('one two three four five six seven');
    $GLOBALS['pkde_test_filters'] = [];
});

it('clamps only a WooCommerce review in the testimonials: the block wrapper carries pkde-quotes--reviews for the reviews source and the typed copy is not in the shared stylesheet (TPL-282)', function () {
    $root = dirname(__DIR__, 2);
    $render = file_get_contents($root . '/resources/blocks/quotes/render.php');
    $css = file_get_contents($root . '/resources/css/clamp.css');

    expect($render)->toContain("\$source === 'reviews' ? ' pkde-quotes--reviews' : ''")
        ->and($css)->toContain('.pkde-quotes--reviews .pkde-quote blockquote')
        ->and($css)->not->toContain('.pkde-feature__title')
        ->and($css)->not->toContain('.pkde-feature__text');
});

it('scrolls a WooCommerce review instead of cutting it (TPL-284): a max height in lines, overflow-y auto, no line clamp, and the box is made focusable by a script', function () {
    $root = dirname(__DIR__, 2);
    $css = file_get_contents($root . '/resources/css/clamp.css');
    $js = file_get_contents($root . '/resources/js/quote-scroll.js');
    $quote = file_get_contents($root . '/resources/views/partials/blocks/quote.blade.php');

    expect($css)->toContain('--pkde-review-lines: var(--pkde-clamp-quote-review, var(--pkde-clamp-quote, 6))')
        ->and($css)->toContain('overflow-y: auto')->toContain('overscroll-behavior: contain')->toContain('scrollbar-width: thin')
        ->and($css)->not->toContain('.pkde-quotes--reviews .pkde-quote blockquote { --pkde-lines')
        ->and($js)->toContain('box.tabIndex = 0')->toContain("'role', 'region'")->toContain('data-pkde-more')
        ->and($quote)->toContain('data-pkde-label')
        ->and(file_get_contents($root . '/resources/js/kit.js'))->toContain("import './quote-scroll.js'")
        ->and(file_get_contents($root . '/app/faq.php'))->toContain('text-[color:var(--section-icon,var(--color-neutral-400))]');
});
