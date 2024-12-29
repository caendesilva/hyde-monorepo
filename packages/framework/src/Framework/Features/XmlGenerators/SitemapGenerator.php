<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Hyde\Framework\Features\XmlGenerators;

use Hyde\Hyde;
use SimpleXMLElement;
use Hyde\Pages\HtmlPage;
use Hyde\Pages\BladePage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Facades\Filesystem;
use Hyde\Pages\InMemoryPage;
use Hyde\Support\Models\Route;
use Illuminate\Support\Carbon;
use Hyde\Pages\DocumentationPage;
use Hyde\Foundation\Facades\Routes;

use function in_array;
use function date;

/**
 * @see https://www.sitemaps.org/protocol.html
 */
class SitemapGenerator extends BaseXmlGenerator
{
    public function generate(): static
    {
        Routes::all()->each(function (Route $route): void {
            $this->addRoute($route);
        });

        return $this;
    }

    protected function constructBaseElement(): void
    {
        $this->xmlElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $this->xmlElement->addAttribute('generator', 'HydePHP v'.Hyde::version());
    }

    protected function addRoute(Route $route): void
    {
        $urlItem = $this->xmlElement->addChild('url');

        $this->addChild($urlItem, 'loc', $this->resolveRouteLink($route));
        $this->addChild($urlItem, 'lastmod', $this->getLastModDate($route->getSourcePath()));
        $this->addChild($urlItem, 'changefreq', $this->generateChangeFrequency(...$this->getRouteInformation($route)));
        $this->addChild($urlItem, 'priority', $this->generatePriority(...$this->getRouteInformation($route)));
    }

    protected function resolveRouteLink(Route $route): string
    {
        return Hyde::url($route->getOutputPath());
    }

    protected function getLastModDate(string $file): string
    {
        return date('c', @Filesystem::lastModified($file) ?: Carbon::now()->timestamp);
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $pageClass
     * @return numeric-string
     */
    protected function generatePriority(string $pageClass, string $identifier): string
    {
        $priority = 0.5;

        if (in_array($pageClass, [BladePage::class, MarkdownPage::class, DocumentationPage::class])) {
            $priority = 0.9;

            if ($identifier === 'index') {
                $priority = 1;
            }
        }

        if (in_array($pageClass, [MarkdownPost::class, InMemoryPage::class, HtmlPage::class])) {
            $priority = 0.75;
        }

        if ($identifier === '404') {
            $priority = 0.25;
        }

        return (string) $priority;
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $pageClass
     * @return 'always'|'hourly'|'daily '|'weekly'|'monthly'|'yearly'|'never'
     */
    protected function generateChangeFrequency(string $pageClass, string $identifier): string
    {
        $frequency = 'weekly';

        if (in_array($pageClass, [BladePage::class, MarkdownPage::class, DocumentationPage::class])) {
            $frequency = 'daily';
        }

        if ($identifier === '404') {
            $frequency = 'monthly';
        }

        return $frequency;
    }

    /** @return array{class-string<\Hyde\Pages\Concerns\HydePage>, string} */
    protected function getRouteInformation(Route $route): array
    {
        return [$route->getPageClass(), $route->getPage()->getIdentifier()];
    }
}
