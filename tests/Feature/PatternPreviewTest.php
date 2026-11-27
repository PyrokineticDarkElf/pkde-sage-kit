<?php

/**
 * The development-only pattern preview: where it answers, which names it accepts, where it looks for a pattern.
 */
use function App\pattern_library_dir;
use function App\pattern_preview_brand_css;
use function App\pattern_preview_enabled;
use function App\pattern_preview_key_ok;
use function App\pattern_preview_markup;

beforeEach(function () {
    $this->library = sys_get_temp_dir() . '/pkde-patterns-' . bin2hex(random_bytes(4));
    // the library: <root>/patterns/<family>/<slug>.html and <root>/dist/brands/<key>.css; PKDE_PATTERNS_DIR is the patterns folder
    mkdir($this->library . '/patterns/hero', 0777, true);
    mkdir($this->library . '/dist/brands', 0777, true);
    $this->patterns = $this->library . '/patterns';
    file_put_contents($this->library . '/patterns/hero/library-hero.html', '<!-- wp:paragraph --><p>From the library</p><!-- /wp:paragraph -->');
    file_put_contents($this->library . '/dist/brands/acme.css', ':root { --brand: 1; }');
    file_put_contents($this->library . '/dist/secret.css', 'SECRET');
});

afterEach(function () {
    foreach (['/dist/brands/acme.css', '/dist/secret.css', '/patterns/hero/library-hero.html'] as $file) {
        @unlink($this->library . $file);
    }
    foreach (['/dist/brands', '/dist', '/patterns/hero', '/patterns', ''] as $dir) {
        @rmdir($this->library . $dir);
    }
});

it('answers only in development', function () {
    expect(pattern_preview_enabled('development'))->toBeTrue()
        ->and(pattern_preview_enabled('staging'))->toBeFalse()
        ->and(pattern_preview_enabled('production'))->toBeFalse()
        ->and(pattern_preview_enabled(''))->toBeFalse();
});

it('accepts a plain slug or brand key and nothing else', function () {
    foreach (['hero-split', 'a1', 'x-y-z'] as $ok) {
        expect(pattern_preview_key_ok($ok))->toBeTrue();
    }
    foreach (['', '../x', 'a/b', 'Hero', 'a b', 'a.php', "a\0b", 'a%2fb', '..', 'a_b'] as $bad) {
        expect(pattern_preview_key_ok($bad))->toBeFalse();
    }
});

it('finds a pattern of the kit, then one of the library, and refuses a bad slug before it touches a file', function () {
    $kit = pattern_preview_markup('hero-split');
    expect($kit)->toContain('wp:sage/hero');

    expect(pattern_preview_markup('library-hero', $this->patterns))->toContain('From the library')
        ->and(pattern_preview_markup('library-hero'))->toBeNull() // no library given
        ->and(pattern_preview_markup('nothing-here', $this->patterns))->toBeNull()
        ->and(pattern_preview_markup('../patterns/hero/library-hero', $this->patterns))->toBeNull()
        ->and(pattern_preview_markup('..', $this->patterns))->toBeNull();
});

it('reads a brand stylesheet of the library by its key only', function () {
    expect(pattern_preview_brand_css('acme', $this->patterns))->toBe(':root { --brand: 1; }')
        ->and(pattern_preview_brand_css('', $this->patterns))->toBe('')
        ->and(pattern_preview_brand_css('missing', $this->patterns))->toBe('')
        ->and(pattern_preview_brand_css('../secret', $this->patterns))->toBe('')
        ->and(pattern_preview_brand_css('acme', null))->toBe('');
});

it('takes the library folder from a real directory only', function () {
    expect(pattern_library_dir($this->patterns))->toBe(realpath($this->patterns))
        ->and(pattern_library_dir($this->patterns . '/nope'))->toBeNull()
        ->and(pattern_library_dir(''))->toBeNull();
});
