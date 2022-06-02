<?php

namespace Hyde\Framework\Concerns\FacadeHelpers;

use Hyde\Framework\Models\DocumentationPage;

trait HydeSmartDocsFacade
{
    public static function create(DocumentationPage $page, string $html): static
    {
        return new static($page, $html);
    }

    public static function isEnabled(): bool
    {
        return config('docs.smart_docs', true);
    }
}