<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Documentation;

use Hyde\Pages\InMemoryPage;
use Hyde\Pages\DocumentationPage;
use Hyde\Support\Models\RouteKey;
use Hyde\Framework\Actions\GeneratesDocumentationSearchIndex;

/**
 * @internal This page is used to render the search index for the documentation.
 */
class DocumentationSearchIndex extends InMemoryPage
{
    /**
     * Create a new DocumentationSearchPage instance.
     */
    public function __construct()
    {
        parent::__construct(DocumentationSearchIndex::outputPath(), [
            'navigation' => ['hidden' => true],
        ]);
    }

    public function compile(): string
    {
        return GeneratesDocumentationSearchIndex::handle();
    }

    public static function outputPath(string $identifier = ''): string
    {
        return RouteKey::fromPage(DocumentationPage::class, 'search').'.json';
    }
}
