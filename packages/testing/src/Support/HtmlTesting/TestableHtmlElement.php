<?php

declare(strict_types=1);

namespace Hyde\Testing\Support\HtmlTesting;

use DOMXPath;
use DOMElement;
use DOMDocument;
use Illuminate\Support\Collection;

/**
 * A wrapper for an HTML element node, parsed into an assertable and queryable object.
 */
class TestableHtmlElement
{
    public readonly string $html;
    public readonly string $tag;
    public readonly string $text;

    /** @var \Illuminate\Support\Collection<\Hyde\Testing\Support\HtmlTesting\TestableHtmlElement> The element's child nodes. */
    public readonly Collection $nodes;

    public readonly int $level;

    public function __construct(string $html, int $level = 0)
    {
        $this->html = $html;
        $this->level = $level;

        $this->tag = $this->parseTag($html);
        $this->text = $this->parseText($html);

        $this->nodes = $this->parseNodes($html);
    }

    protected function parseNodes(string $html): Collection
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        $nodes = collect($xpath->query('//*'));

        // Forget the first node, which is the document itself.
        $nodes->forget(0);

        return $nodes->map(function (DOMElement $node): TestableHtmlElement {
            return new TestableHtmlElement($node->ownerDocument->saveHTML($node), $this->level + 1);
        });
    }

    protected function parseTag(string $html): string
    {
        preg_match('/^<([a-z0-9-]+)/i', $html, $matches);

        return $matches[1] ?? '';
    }

    protected function parseText(string $html): string
    {
        preg_match('/>([^<]+)</', $html, $matches);

        return trim(strip_tags($matches[1] ?? ''));
    }
}
