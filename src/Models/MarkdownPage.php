<?php

namespace Hyde\Framework\Models;

/** @inheritDoc */
class MarkdownPage extends MarkdownDocument
{
    public string $title;
    public string $slug;

    public function __construct(array $matter, string $body, string $slug, string $title)
    {
        parent::__construct($matter, $body);
        $this->title = $title;
        $this->slug = $slug;
    }
}
