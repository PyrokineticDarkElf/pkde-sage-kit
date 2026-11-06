<?php

// app/single-post.php (the blog single's helpers, MGR-66): the schema and the share links are built from plain values, so no WordPress is needed.
require_once dirname(__DIR__, 2) . '/app/single-post.php';

use function App\article_schema;
use function App\related_post_ids;
use function App\schema_json;
use function App\share_links;

$root = dirname(__DIR__, 2);

it('builds the Article schema from plain facts and leaves out what is empty', function () {
    $schema = article_schema([
        'title' => 'Ten tips', 'url' => 'https://example.test/ten-tips/', 'published' => '2026-11-01T09:00:00+00:00', 'modified' => '2026-11-02T09:00:00+00:00',
        'description' => 'A short summary.', 'image' => 'https://example.test/a.jpg', 'author' => 'Jo Writer', 'author_url' => 'https://example.test/author/jo/',
        'site' => 'Example Co', 'logo' => 'https://example.test/logo.png',
    ]);

    expect($schema['@type'])->toBe('Article')
        ->and($schema['headline'])->toBe('Ten tips')
        ->and($schema['mainEntityOfPage']['@id'])->toBe('https://example.test/ten-tips/')
        ->and($schema['author'])->toBe(['@type' => 'Person', 'name' => 'Jo Writer', 'url' => 'https://example.test/author/jo/'])
        ->and($schema['publisher']['logo'])->toBe(['@type' => 'ImageObject', 'url' => 'https://example.test/logo.png']);

    $bare = article_schema(['title' => 'T', 'url' => 'https://example.test/t/', 'published' => '2026-11-01T09:00:00+00:00']);

    expect($bare)->not->toHaveKeys(['image', 'author', 'publisher', 'description'])
        ->and($bare['dateModified'])->toBe('2026-11-01T09:00:00+00:00');
});

it('encodes the schema so it cannot close the script element', function () {
    $json = schema_json(article_schema(['title' => '</script><script>alert(1)</script> & "quotes"', 'url' => 'https://example.test/x/', 'published' => '2026-11-01T09:00:00+00:00']));

    expect($json)->not->toContain('</script>')->not->toContain('<')->and(json_decode($json, true)['headline'])->toBe('</script><script>alert(1)</script> & "quotes"');
});

it('builds the share links with the address and the title encoded', function () {
    $links = share_links('https://example.test/a post/?x=1&y=2', 'Tips & tricks');

    expect(array_keys($links))->toBe(['Facebook', 'X', 'LinkedIn', 'WhatsApp', 'Email'])
        ->and($links['Facebook'])->toBe('https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fexample.test%2Fa%20post%2F%3Fx%3D1%26y%3D2')
        ->and($links['X'])->toContain('text=Tips%20%26%20tricks')
        ->and($links['Email'])->toStartWith('mailto:?subject=Tips%20%26%20tricks&body=');
});

it('remembers the related posts so the recent list does not repeat them', function () {
    expect(related_post_ids([3, '4']))->toBe([3, 4])->and(related_post_ids())->toBe([3, 4]);
});

it('keeps the single post layout in one kit view with documented hooks, sections and variables', function () use ($root) {
    $view = file_get_contents("$root/resources/views/partials/content-single.blade.php");
    $doc = file_get_contents("$root/docs/blog-single.md");
    $css = file_get_contents("$root/resources/css/post-single.css");

    foreach (['before_hero', 'after_hero', 'before_meta', 'after_meta', 'before_content', 'after_content', 'before_sidebar', 'after_sidebar', 'before_related', 'after_related'] as $hook) {
        expect($view)->toContain("'pkde_kit/single_$hook'")->and($doc)->toContain("pkde_kit/single_$hook");
    }

    foreach (App\SINGLE_SECTIONS as $section) {
        expect($doc)->toContain("`$section`");
    }

    foreach (['line', 'muted', 'ink', 'accent', 'surface', 'title-weight', 'title-size', 'heading-weight', 'text-size', 'measure', 'rail-width', 'sticky-top', 'hero-radius'] as $name) {
        expect($css)->toContain("--pkde-post-$name")->and($doc)->toContain("--pkde-post-$name");
    }

    expect($css)->not->toContain('--wp--preset')->not->toMatch('/#[0-9a-f]{3,8}\b/i')
        ->and($view)->toContain('post-hero', 'post-body', 'post-rail', 'post-after', 'application/ld+json', "class_exists('RankMath')")
        ->and(file_get_contents("$root/resources/css/kit.css"))->toContain('@import "./post-single.css";')
        ->and(file_exists("$root/resources/views/single.blade.php"))->toBeTrue()
        ->and(file_exists("$root/resources/views/page.blade.php"))->toBeTrue();
});

it('links the byline to the author archive only while it exists, in the meta, the author box and the schema', function () use ($root) {
    foreach (['entry-meta', 'author-box'] as $partial) {
        expect(file_get_contents("$root/resources/views/partials/$partial.blade.php"))->toContain('byline_links_to_archive()');
    }

    expect(file_get_contents("$root/app/single-post.php"))->toContain("'disable_author_archives'")->toContain("'pkde_kit/author_archives'");
});

it('sizes the page header title with variables and lets a site skip the header per page (tcs and bb parity, MGR-77)', function () use ($root) {
    $css = file_get_contents("$root/resources/css/page-header.css");

    expect($css)->toContain('--pkde-page-header-title-size:', '--pkde-page-header-title-size-lg:', '--pkde-page-header-title-leading:', 'font-size: var(--pkde-page-header-title-size)')
        ->and(file_get_contents("$root/resources/views/page.blade.php"))->toContain("'pkde_kit/page_header'", '! is_front_page()', 'partials.page-header');
});
