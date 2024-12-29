<?php

declare(strict_types=1);

namespace Hyde\Testing;

use Hyde\Pages\InMemoryPage;
use Hyde\Support\Models\Route;
use Hyde\Foundation\HydeKernel;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Foundation\Kernel\RouteCollection;
use Illuminate\Support\Collection;

use function collect;
use function is_string;

/**
 * A trait to mock kernel features for testing.
 *
 * @property \Hyde\Testing\TestKernel $kernel
 */
trait MocksKernelFeatures
{
    /**
     * Create a new mock kernel with the given pages added to its routes.
     *
     * @param  array<\Hyde\Pages\Concerns\HydePage|string>  $pages
     * @return $this
     */
    protected function withPages(array $pages): static
    {
        $this->setupTestKernel();

        // If the given pages are strings, convert them to InMemoryPage instances.
        $pages = collect($pages)->map(fn (HydePage|string $page): HydePage => is_string($page) ? new InMemoryPage($page) : $page);
        $routes = collect($pages)->map(fn (HydePage $page) => $page->getRoute());

        $this->kernel->setPages($pages);
        $this->kernel->setRoutes($routes);

        return $this;
    }

    protected function setupTestKernel(): TestKernel
    {
        $this->kernel = new TestKernel();

        HydeKernel::setInstance($this->kernel);

        return $this->kernel;
    }
}

class TestKernel extends HydeKernel
{
    protected ?PageCollection $mockPages = null;
    protected ?RouteCollection $mockRoutes = null;

    public function setPages(Collection $pages): void
    {
        $this->mockPages = PageCollection::make($pages->mapWithKeys(fn (HydePage $page) => [$page->getIdentifier() => $page]));
    }

    public function setRoutes(Collection $routes): void
    {
        $this->mockRoutes = RouteCollection::make($routes->mapWithKeys(fn (Route $route) => [$route->getRouteKey() => $route]));
    }

    /** @return \Hyde\Foundation\Kernel\PageCollection<string, \Hyde\Pages\Concerns\HydePage> */
    public function pages(): PageCollection
    {
        return $this->mockPages ?? parent::pages();
    }

    /** @return \Hyde\Foundation\Kernel\RouteCollection<string, \Hyde\Support\Models\Route> */
    public function routes(): RouteCollection
    {
        return $this->mockRoutes ?? parent::routes();
    }
}
