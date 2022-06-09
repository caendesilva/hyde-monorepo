<?php

namespace Hyde\Framework\Models\Parsers;

use Hyde\Framework\Contracts\AbstractPageParser;
use Hyde\Framework\Hyde;
use Hyde\Framework\Models\MarkdownPage;
use Hyde\Framework\Services\MarkdownFileService;

/**
 * Parses a Markdown file into a MarkdownPage object using the MarkdownDocument intermediary.
 */
class MarkdownPageParser extends AbstractPageParser
{
    protected string $pageModel = MarkdownPage::class;
    protected string $slug;

    public string $title;
    public string $body;

    public function execute(): void
    {
        $document = (new MarkdownFileService(
            Hyde::getMarkdownPagePath("/$this->slug.md")
        ))->get();

        $this->title = $document->findTitleForDocument();

        $this->body = $document->body;
    }

    public function get(): MarkdownPage
    {
        return new MarkdownPage(
            matter: [],
            body: $this->body,
            title: $this->title,
            slug: $this->slug
        );
    }
}
