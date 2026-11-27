<?php

// app/legal.php: the dates line and the contents list of a legal document; plain values, no WordPress. The view is proved in a browser on the template stack.
require_once dirname(__DIR__, 2) . '/app/TableOfContents.php';
require_once dirname(__DIR__, 2) . '/app/legal.php';

use function App\legal_meta_parts;
use function App\legal_toc;

$root = dirname(__DIR__, 2);
$format = fn(string $ymd) => date('j F Y', strtotime($ymd));

it('prints the dates line with only the parts that are set, the reviewed date winning over the modified one', function () use ($format) {
    expect(legal_meta_parts(['effective' => '2026-11-01', 'reviewed' => '2026-11-12', 'version' => '1.3'], '2026-11-20', $format))
        ->toBe(['Effective from 1 November 2026', 'Last updated 12 November 2026', 'Version 1.3'])
        ->and(legal_meta_parts(['effective' => '2026-11-01'], '2026-11-20', $format))->toBe(['Effective from 1 November 2026', 'Last updated 20 November 2026'])
        ->and(legal_meta_parts([], '2026-11-20', $format))->toBe(['Last updated 20 November 2026'])
        ->and(legal_meta_parts(['version' => '2'], '', $format))->toBe(['Version 2'])
        ->and(legal_meta_parts([], '', $format))->toBe([]);
});

it('adds ids to the headings and lists only h2 and h3, from three of them', function () {
    foreach (['sanitize_title' => fn($t) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $t), '-')), '__' => fn($t) => $t] as $name => $impl) {
        if (! function_exists($name)) {
            $GLOBALS['pkde_stub_' . $name] = $impl;
            eval("function $name(\$a) { return \$GLOBALS['pkde_stub_$name'](\$a); }");
        }
    }

    $html = '<p>Intro</p><h2>Who we are</h2><p>a</p><h3>Contact</h3><p>b</p><h4>Detail</h4><h2>Your rights</h2><p>c</p>';
    $toc = legal_toc($html);

    expect(array_column($toc['headings'], 'text'))->toBe(['Who we are', 'Contact', 'Your rights']) // the h4 is not listed
        ->and(array_column($toc['headings'], 'id'))->toBe(['who-we-are', 'contact', 'your-rights'])
        ->and($toc['show'])->toBeTrue()
        ->and($toc['content'])->toContain('id="who-we-are"')->and($toc['content'])->toContain('id="detail"'); // every heading has an anchor

    // two headings: no list
    expect(legal_toc('<h2>One</h2><p>x</p><h2>Two</h2>')['show'])->toBeFalse()
        ->and(legal_toc('')['show'])->toBeFalse();
});

it('keeps accents, dashes, CJK and emoji intact and raises no deprecation', function () {
    foreach (['sanitize_title' => fn($t) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $t), '-')), '__' => fn($t) => $t] as $name => $impl) {
        if (! function_exists($name)) {
            $GLOBALS['pkde_stub_' . $name] = $impl;
            eval("function $name(\$a) { return \$GLOBALS['pkde_stub_$name'](\$a); }");
        }
    }

    $deprecations = [];
    set_error_handler(function ($no, $message) use (&$deprecations) {
        $deprecations[] = $message;

        return true;
    }, E_DEPRECATED);
    $toc = App\TableOfContents::fromContent('<p>Café — “quoted” 日本語 😀 &amp; naïve</p><h2>Qué es esto — ¿ahora?</h2><p>Zoë’s 😀</p><h3>日本語 heading</h3>');
    restore_error_handler();

    expect($deprecations)->toBe([])
        ->and($toc['content'])->toContain('Café — “quoted” 日本語 😀 &amp; naïve')->toContain('Zoë’s 😀')
        ->and(array_column($toc['headings'], 'text'))->toBe(['Qué es esto — ¿ahora?', '日本語 heading']);
});
