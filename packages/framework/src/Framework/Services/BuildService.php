<?php

declare(strict_types=1);

namespace Hyde\Framework\Services;

use Hyde\Hyde;
use Hyde\Pages\InMemoryPage;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\Kernel\RouteCollection;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Models\Route;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;

use function class_basename;
use function preg_replace;
use function collect;

/**
 * Moves logic from the build command to a service.
 *
 * Handles the build loop which generates the static site.
 *
 * @see \Hyde\Console\Commands\BuildSiteCommand
 */
class BuildService
{
    use InteractsWithIO;

    protected RouteCollection $router;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;

        $this->router = Hyde::routes();
    }

    public function compileStaticPages(): void
    {
        collect($this->getPageTypes())->each(function (string $pageClass): void {
            $this->compilePagesForClass($pageClass);
        });
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $pageClass
     */
    protected function compilePagesForClass(string $pageClass): void
    {
        $this->comment("Creating {$this->getClassPluralName($pageClass)}...");

        $collection = Routes::getRoutes($pageClass);

        $this->withProgressBar($collection, function (Route $route): void {
            StaticPageBuilder::handle($route->getPage());
        });

        $this->newLine(2);
    }

    protected function getClassPluralName(string $pageClass): string
    {
        if ($pageClass === InMemoryPage::class) {
            return 'Dynamic Pages';
        }

        return preg_replace('/([a-z])([A-Z])/', '$1 $2', class_basename($pageClass)).'s';
    }

    /** @return array<class-string<\Hyde\Pages\Concerns\HydePage>> */
    protected function getPageTypes(): array
    {
        return Hyde::pages()->map(function (HydePage $page): string {
            if ($page instanceof InMemoryPage) {
                return InMemoryPage::class;
            }

            return $page::class;
        })->unique()->values()->toArray();
    }
}
