<?php

// app/single-post.php (the blog single's helpers): the schema and the share links are built from plain values, so no WordPress is needed.
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

it('links the byline to the author archive only while it exists, in the meta, the author box and the schema', function () use ($root) {
    foreach (['entry-meta', 'author-box'] as $partial) {
        expect(file_get_contents("$root/resources/views/partials/$partial.blade.php"))->toContain('byline_links_to_archive()');
    }

    expect(file_get_contents("$root/app/single-post.php"))->toContain("'disable_author_archives'")->toContain("'pkde_kit/author_archives'");
});
