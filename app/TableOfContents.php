<?php

namespace App;

/**
 * Adds safe anchors and copy-link controls to rendered content headings, then
 * extracts the matching list for the table of contents and its scroll-spy.
 */
class TableOfContents
{
    /**
     * @return array{content: string, headings: array<int, array{level: int, text: string, id: string}>}
     */
    public static function fromContent(string $html): array
    {
        if (trim($html) === '') {
            return ['content' => $html, 'headings' => []];
        }

        $document = new \DOMDocument();

        // DOMDocument assumes ISO-8859-1 without an explicit encoding hint,
        // mangling anything non-ASCII in the content; the mb_convert_encoding
        // trick forces it to treat the input as UTF-8 without needing a
        // <meta charset> in the fragment. Errors are suppressed because
        // block-rendered HTML fragments aren't full documents and libxml
        // otherwise warns about that.
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//h2 | //h3 | //h4 | //h5 | //h6');

        $headings = [];

        // Existing IDs can be deliberately authored anchors. Count every ID
        // before touching headings: a unique, non-whitespace heading ID is
        // safe to retain; an ID shared by another element is not.
        $idCounts = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            $id = trim($element->getAttribute('id'));
            if ($id !== '') {
                $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
            }
        }

        foreach ($nodes as $node) {
            $text = trim($node->textContent);

            if ($text === '') {
                continue;
            }

            $existingId = trim($node->getAttribute('id'));
            $id = self::isValidId($existingId) && ($idCounts[$existingId] ?? 0) === 1
                ? $existingId
                : self::uniqueId(sanitize_title($text) ?: 'section', $idCounts);

            $node->setAttribute('id', $id);
            self::appendCopyControl($document, $node, $text);

            $headings[] = [
                'level' => (int) substr($node->nodeName, 1),
                'text' => $text,
                'id' => $id,
            ];
        }

        $wrapper = $document->getElementsByTagName('div')->item(0);
        $content = '';

        foreach ($wrapper->childNodes as $child) {
            $content .= $document->saveHTML($child);
        }

        return ['content' => $content, 'headings' => $headings];
    }

    private static function isValidId(string $id): bool
    {
        return $id !== '' && preg_match('/^\S+$/u', $id) === 1;
    }

    /** @param array<string, int> $idCounts */
    private static function uniqueId(string $base, array &$idCounts): string
    {
        $candidate = $base;
        $suffix = 2;

        while (isset($idCounts[$candidate])) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        $idCounts[$candidate] = 1;

        return $candidate;
    }

    private static function appendCopyControl(\DOMDocument $document, \DOMElement $heading, string $text): void
    {
        $button = $document->createElement('button');
        $button->setAttribute('type', 'button');
        $button->setAttribute('class', 'heading-anchor-copy');
        $button->setAttribute('data-heading-copy', '');
        $button->setAttribute('aria-label', sprintf(__('Copy link to %s', 'sage'), $text));
        $button->setAttribute('title', __('Copy link to this section', 'sage'));

        $icon = $document->createElementNS('http://www.w3.org/2000/svg', 'svg');
        $icon->setAttribute('viewBox', '0 0 24 24');
        $icon->setAttribute('aria-hidden', 'true');
        $icon->setAttribute('focusable', 'false');
        $path = $document->createElementNS('http://www.w3.org/2000/svg', 'path');
        $path->setAttribute('d', 'M10 13a5 5 0 0 0 7.07.07l2-2a5 5 0 0 0-7.07-7.07l-1.15 1.15M14 11a5 5 0 0 0-7.07-.07l-2 2A5 5 0 0 0 12 20l1.15-1.15');
        $icon->appendChild($path);
        $button->appendChild($icon);

        $label = $document->createElement('span', __('Copy link', 'sage'));
        $label->setAttribute('class', 'screen-reader-text');
        $button->appendChild($label);
        $heading->appendChild($button);
    }
}
