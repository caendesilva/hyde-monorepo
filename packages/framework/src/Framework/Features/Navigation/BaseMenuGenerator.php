<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Facades\Config;
use Hyde\Support\Models\Route;
use Hyde\Pages\DocumentationPage;
use Illuminate\Support\Collection;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\Kernel\RouteCollection;

use function filled;

/**
 * @experimental This class may change significantly before its release.
 */
abstract class BaseMenuGenerator
{
    /** @var \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Navigation\NavItem> */
    protected Collection $items;

    /** @var \Hyde\Foundation\Kernel\RouteCollection<string, \Hyde\Support\Models\Route> */
    protected RouteCollection $routes;

    protected bool $generatesSidebar;
    protected bool $usesGroups;

    protected function __construct()
    {
        $this->items = new Collection();

        $this->generatesSidebar = $this instanceof GeneratesDocumentationSidebarMenu;

        $this->routes = $this->generatesSidebar
            ? Routes::getRoutes(DocumentationPage::class)
            : Routes::all();

        $this->usesGroups = $this->usesGroups();
    }

    public static function handle(): NavigationMenu
    {
        $menu = new static();

        $menu->generate();

        return new NavigationMenu($menu->items);
    }

    abstract protected function generate(): void;

    protected function usesGroups(): bool
    {
        if ($this->generatesSidebar) {
            // In order to know if we should use groups in the sidebar,
            // we need to loop through the pages and see if they have a group set

            return $this->routes->first(function (Route $route): bool {
                return filled($route->getPage()->navigationMenuGroup());
            }) !== null;
        } else {
            return Config::getString('hyde.navigation.subdirectories', 'hidden') === 'dropdown';
        }
    }
}
