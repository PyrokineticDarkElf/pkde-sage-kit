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

it('builds every pattern from kit and curated core blocks, with the blocks\' own attributes, in a Section locked to content', function () {
    $root = dirname(__DIR__, 2);
    $core = ['core/paragraph', 'core/heading', 'core/columns', 'core/column', 'core/buttons', 'core/button', 'core/list', 'core/list-item', 'core/image', 'core/separator'];
    $known = [];

    foreach (glob("$root/resources/blocks/*/block.json") as $file) {
        $json = json_decode((string) file_get_contents($file), true);
        $known[$json['name']] = $json['attributes'] ?? [];
    }

    foreach (pkdeBank() as $file => [$header, $content]) {
        // the outermost block is a locked Section
        expect($content)->toStartWith('<!-- wp:sage/section ')->and($content)->toContain('"templateLock":"contentOnly"');
        $depth = 0;

        preg_match_all('/<!-- (\/?)wp:([a-z0-9\/-]+)( \{.*?\})?( ?\/)? ?-->/s', $content, $tags, PREG_SET_ORDER);

        foreach ($tags as $tag) {
            [, $close, $name, $json, $self] = $tag + [4 => ''];
            $name = str_contains($name, '/') ? $name : "core/{$name}";
            expect(array_key_exists($name, $known) || in_array($name, $core, true))->toBeTrue("$file uses $name");

            if ($json !== '' && $json !== null) {
                $attributes = json_decode(trim($json), true);
                expect($attributes)->toBeArray("$file: the attributes of $name are valid JSON");

                if (array_key_exists($name, $known)) {
                    foreach ($attributes as $key => $value) {
                        if (in_array($key, ['className', 'align', 'templateLock', 'anchor'], true)) {
                            continue;
                        }

                        expect(array_key_exists($key, $known[$name]))->toBeTrue("$file: $name has no attribute $key");
                        $schema = $known[$name][$key];

                        if (isset($schema['enum'])) {
                            expect(in_array($value, $schema['enum'], true))->toBeTrue("$file: $name.$key = " . json_encode($value));
                        }
                    }
                }
            }

            if ($close === '/') {
                $depth--;
            } elseif (trim((string) $self) === '' && ! str_ends_with(trim($tag[0]), '/-->')) {
                $depth++;
            }
        }

        expect($depth)->toBe(0, "$file has balanced block comments");
        expect(strtolower($content))->not->toContain('lorem ipsum')->and($content)->not->toMatch('/https?:\/\//');
    }
});
