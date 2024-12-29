<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Facades\Config;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Pages\DocumentationPage;
use Illuminate\Support\Collection;

use function basename;
use function in_array;
use function trim;

/**
 * @internal Generate a JSON string that can be used as a search index for documentation pages.
 */
class GeneratesDocumentationSearchIndex
{
    use InteractsWithDirectories;

    protected Collection $index;

    /**
     * @since v2.x This method returns the JSON string instead of saving it to disk and returning the path.
     *
     * @return string The path to the generated file.
     */
    public static function handle(): string
    {
        $service = new static();
        $service->run();

        return $service->index->toJson();
    }

    protected function __construct()
    {
        $this->index = new Collection();
    }

    protected function run(): void
    {
        DocumentationPage::all()->each(function (DocumentationPage $page): void {
            if (! in_array($page->identifier, $this->getPagesToExcludeFromSearch())) {
                $this->index->push($this->generatePageEntry($page));
            }
        });
    }

    /**
     * @return array{slug: string, title: string, content: string, destination: string}
     */
    protected function generatePageEntry(DocumentationPage $page): array
    {
        return [
            'slug' => basename($page->identifier),
            'title' => $page->title,
            'content' => trim($this->getSearchContentForDocument($page)),
            'destination' => $this->formatDestination(basename($page->identifier)),
        ];
    }

    protected function getSearchContentForDocument(DocumentationPage $page): string
    {
        return (new ConvertsMarkdownToPlainText($page->markdown->body()))->execute();
    }

    protected function formatDestination(string $slug): string
    {
        if (Config::getBool('hyde.pretty_urls', false) === true) {
            return $slug === 'index' ? '' : $slug;
        }

        return "$slug.html";
    }

    protected function getPagesToExcludeFromSearch(): array
    {
        return array_merge(Config::getArray('docs.exclude_from_search', []),
            Config::getBool('docs.create_search_page', true) ? ['search'] : []
        );
    }
}
