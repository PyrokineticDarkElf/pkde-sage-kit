<?php

// The starter set (the other patterns live in the pkde-patterns library): every pattern is "Family: Variant", has its family's one category, is built from kit and curated core blocks only, its attributes are the blocks' own,
// its outer Section is locked to content, and nothing in it is lorem ipsum or a link off the site. The editor side (0 invalid blocks as Editor and as Administrator) and the look
// (3 themes x 3 shapes x 2 widths) are proved on the template stack.
require_once dirname(__DIR__, 2) . '/app/patterns.php';
require_once dirname(__DIR__, 2) . '/app/sections.php';

/** The header of a pattern file (the docblock's "Key: value" lines) and its content. */
function pkdeBank(): array
{
    static $bank = null;

    if ($bank !== null) {
        return $bank;
    }

    $bank = [];

    foreach (glob(dirname(__DIR__, 2) . '/resources/patterns/*.php') as $file) {
        preg_match_all('/^ \* ([A-Za-z ]+): (.*)$/m', (string) file_get_contents($file), $m, PREG_SET_ORDER);
        $header = [];

        foreach ($m as [, $key, $value]) {
            $header[strtolower($key)] = trim($value);
        }

        ob_start();
        include $file;
        $bank[basename($file)] = [$header + ['categories' => '', 'slug' => '', 'title' => ''], trim((string) ob_get_clean())];
    }

    return $bank;
}

function pkdeSections(): array
{
    return array_filter(pkdeBank(), fn($entry) => $entry[0]['categories'] !== 'pkde-pages');
}

it('ships exactly the starter set, one family each, named "Family: Variant", slugs unique', function () {
    $families = \App\pattern_families();
    $slugs = array_values(array_map(fn($entry) => $entry[0]['slug'], pkdeBank()));

    foreach (pkdeSections() as [$header]) {
        expect($header['categories'])->toStartWith('pkde-')->and($header['categories'])->not->toContain(',');
        $family = substr($header['categories'], 5);
        expect($families)->toHaveKey($family)
            ->and($header['title'])->toStartWith($families[$family] . ': ')
            ->and($header['slug'])->toStartWith('pkde/');
    }

    expect($slugs)->toEqualCanonicalizing(['pkde/hero-split', 'pkde/page-about', 'pkde/contact-beside-form', 'pkde/faq-one-column', 'pkde/cta-split', 'pkde/features-cards', 'pkde/testimonials-cards', 'pkde/text-and-image'])
        ->and(array_unique($slugs))->toHaveCount(count($slugs));
});
