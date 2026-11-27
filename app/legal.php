<?php

/**
 * The legal document view (the platform's `legal` module makes the post type): the line under the title and the table of contents.
 * Pure functions; resources/views/single-pkde_legal.blade.php prints them.
 */

namespace App;

/**
 * The parts of the line under a legal document's title, only those that are set:
 * "Effective from 1 November 2026", "Last updated 12 November 2026", "Version 1.3".
 * "Last updated" is the reviewed date when there is one, else the date the document was last changed.
 *
 * @param  array{effective?: string, reviewed?: string, version?: string}  $meta
 * @param  string  $modified  `Y-m-d` of the document's last change
 * @param  callable(string): string  $format  a `Y-m-d` date as the site shows dates
 * @return list<string>
 */
function legal_meta_parts(array $meta, string $modified, callable $format): array
{
    $parts = [];

    if (($meta['effective'] ?? '') !== '') {
        $parts[] = 'Effective from ' . $format($meta['effective']);
    }

    $updated = ($meta['reviewed'] ?? '') !== '' ? $meta['reviewed'] : $modified;

    if ($updated !== '') {
        $parts[] = 'Last updated ' . $format($updated);
    }

    if (($meta['version'] ?? '') !== '') {
        $parts[] = 'Version ' . $meta['version'];
    }

    return $parts;
}

/**
 * The content with an id on every heading, and the contents list: the h2 and h3 headings only, shown from three of them (a short notice needs none).
 *
 * @return array{content: string, headings: list<array{level: int, text: string, id: string}>, show: bool}
 */
function legal_toc(string $html): array
{
    $toc = TableOfContents::fromContent($html);
    $headings = array_values(array_filter($toc['headings'], fn($heading) => in_array($heading['level'], [2, 3], true)));

    return ['content' => $toc['content'], 'headings' => $headings, 'show' => count($headings) >= 3];
}
