<?php

/**
 * The one server-side cap on card text: plain text, entities decoded once, at most 40 words (the filter changes it), no "Continued". WordPress is
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
