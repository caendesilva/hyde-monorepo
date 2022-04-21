<?php

namespace Hyde\Framework;

use Hyde\Framework\Models\DocumentationPage;
use Hyde\Framework\Services\MarkdownFileService;

class DocumentationPageParser extends AbstractPageParser
{
    protected string $pageModel = DocumentationPage::class;
    protected string $slug;

    public string $title;
    public string $body;

    public function execute(): void
    {
        $document = (new MarkdownFileService(
            Hyde::path("_docs/$this->slug.md")
        ))->get();

        $this->title = $document->findTitleForDocument();

        $this->body = $document->body;
    }

    public function get(): DocumentationPage
    {
        return new DocumentationPage(
            matter: [],
            body: $this->body,
            title: $this->title,
            slug: $this->slug
        );
    }
}
