<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Documentation;

use Hyde\Hyde;
use Hyde\Pages\InMemoryPage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Models\RouteKey;
use Hyde\Facades\Config;

/**
 * @internal This page is used to render the search page for the documentation.
 *
 * It is not based on a source file, but is dynamically generated when the Search feature is enabled.
 * If you want to override this page, you can create a page with the route key "docs/search",
 * then this class will not be applied. For example, `_pages/docs/search.blade.php`.
 */
class DocumentationSearchPage extends InMemoryPage
{
    /**
     * Create a new DocumentationSearchPage instance.
     */
    public function __construct()
    {
        parent::__construct(static::routeKey(), [
            'title' => 'Search',
            'navigation' => ['hidden' => true],
            'article' => false,
        ], view: 'hyde::pages.docs.search');
    }

    public static function enabled(): bool
    {
        return Config::getBool('docs.create_search_page', true) && ! static::anotherSearchPageExists();
    }

    public static function routeKey(): string
    {
        return RouteKey::fromPage(DocumentationPage::class, 'search')->get();
    }

    protected static function anotherSearchPageExists(): bool
    {
        // Since routes aren't discovered yet due to this page being added in the core extension,
        // we need to check the page collection directly, instead of the route collection.
        return Hyde::pages()->first(fn (HydePage $file): bool => $file->getRouteKey() === static::routeKey()) !== null;
    }
}
