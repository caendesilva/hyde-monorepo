<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Support\Models\Route;
use Hyde\Pages\DocumentationPage;
use Illuminate\Support\Collection;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\Kernel\RouteCollection;
use Hyde\Framework\Exceptions\InvalidConfigurationException;

use function filled;
use function assert;
use function collect;
use function in_array;
use function strtolower;

/**
 * @experimental This class may change significantly before its release.
 */
class NavigationMenuGenerator
{
    /** @var \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Navigation\NavigationItem|\Hyde\Framework\Features\Navigation\NavigationGroup> */
    protected Collection $items;

    /** @var \Hyde\Foundation\Kernel\RouteCollection<string, \Hyde\Support\Models\Route> */
    protected RouteCollection $routes;

    /** @var class-string<\Hyde\Framework\Features\Navigation\NavigationMenu> */
    protected string $menuType;

    protected bool $generatesSidebar;
    protected bool $usesGroups;

    /** @param class-string<\Hyde\Framework\Features\Navigation\NavigationMenu> $menuType */
    protected function __construct(string $menuType)
    {
        assert(in_array($menuType, [MainNavigationMenu::class, DocumentationSidebar::class]));

        $this->menuType = $menuType;

        $this->items = new Collection();

        $this->generatesSidebar = $menuType === DocumentationSidebar::class;

        $this->routes = $this->generatesSidebar
            ? Routes::getRoutes(DocumentationPage::class)
            : Routes::all();

        $this->usesGroups = $this->usesGroups();
    }

    /** @param class-string<\Hyde\Framework\Features\Navigation\NavigationMenu> $menuType */
    public static function handle(string $menuType): MainNavigationMenu|DocumentationSidebar
    {
        $menu = new static($menuType);

        $menu->generate();

        return new $menuType($menu->items);
    }

    protected function generate(): void
    {
        $this->routes->each(function (Route $route): void {
            if ($this->canAddRoute($route)) {
                if ($this->canGroupRoute($route)) {
                    $this->addRouteToGroup($route);
                } else {
                    $this->items->put($route->getRouteKey(), NavigationItem::create($route));
                }
            }
        });

        if ($this->generatesSidebar) {
            // If there are no pages other than the index page, we add it to the sidebar so that it's not empty
            if ($this->items->count() === 0 && DocumentationPage::home() !== null) {
                $this->items->push(NavigationItem::create(DocumentationPage::home()));
            }
        } else {
            collect(Config::getArray('hyde.navigation.custom', []))->each(function (array $data): void {
                /** @var array{destination: string, label: ?string, priority: ?int, attributes: array<string, scalar>} $data */
                $message = 'Invalid navigation item configuration detected the configuration file. Please double check the syntax.';
                $item = InvalidConfigurationException::try(fn () => NavigationItem::create(...$data), $message);

                // Since these were added explicitly by the user, we can assume they should always be shown
                $this->items->push($item);
            });
        }
    }

    protected function usesGroups(): bool
    {
        if ($this->generatesSidebar) {
            // In order to know if we should use groups in the sidebar, we need to loop through the pages and see if they have a group set.
            // This automatically enables the sidebar grouping for all pages if at least one group is set.

            return $this->routes->first(fn (Route $route): bool => filled($route->getPage()->navigationMenuGroup())) !== null;
        } else {
            return Config::getString('hyde.navigation.subdirectory_display', 'hidden') === 'dropdown';
        }
    }

    protected function canAddRoute(Route $route): bool
    {
        if (! $route->getPage()->showInNavigation()) {
            return false;
        }

        if ($this->generatesSidebar) {
            // Since the index page is linked in the header, we don't want it in the sidebar
            return ! $route->is(DocumentationPage::homeRouteName());
        } else {
            // While we for the most part can rely on the navigation visibility state provided by the navigation data factory,
            // we need to make an exception for documentation pages, which generally have a visible state, as the data is
            // also used in the sidebar. But we only want the documentation index page to be in the main navigation.
            return ! $route->getPage() instanceof DocumentationPage || $route->is(DocumentationPage::homeRouteName());
        }
    }

    protected function canGroupRoute(Route $route): bool
    {
        if (! $this->generatesSidebar) {
            return $route->getPage()->navigationMenuGroup() !== null;
        }

        if (! $this->usesGroups) {
            return false;
        }

        return true;
    }

    protected function addRouteToGroup(Route $route): void
    {
        $item = NavigationItem::create($route);

        $groupKey = $item->getPage()->navigationMenuGroup();
        $groupName = $this->generatesSidebar ? ($groupKey ?? 'Other') : $groupKey;

        $groupItem = $this->getOrCreateGroupItem($groupName);

        $groupItem->add($item);

        if (! $this->items->has($groupItem->getGroupKey())) {
            $this->items->put($groupItem->getGroupKey(), $groupItem);
        }
    }

    protected function getOrCreateGroupItem(string $groupName): NavigationGroup
    {
        $groupKey = NavigationGroup::normalizeGroupKey($groupName);
        $group = $this->items->get($groupKey);

        if ($group instanceof NavigationGroup) {
            return $group;
        } elseif ($group instanceof NavigationItem) {
            // We are trying to add children to an existing navigation menu item,
            // so here we create a new instance to replace the base one, this
            // does mean we lose the destination as we can't link to them.

            $item = new NavigationGroup($group->getLabel(), [], $group->getPriority());

            $this->items->put($groupKey, $item);

            return $item;
        }

        return $this->createGroupItem($groupKey, $groupName);
    }

    protected function createGroupItem(string $groupKey, string $groupName): NavigationGroup
    {
        $label = $this->searchForGroupLabelInConfig($groupKey) ?? $groupName;

        $priority = $this->searchForGroupPriorityInConfig($groupKey);

        return NavigationGroup::create($this->normalizeGroupLabel($label), [], $priority ?? NavigationMenu::LAST);
    }

    protected function normalizeGroupLabel(string $label): string
    {
        // If there is no label, and the group is a slug, we can make a title from it
        if ($label === strtolower($label)) {
            return Hyde::makeTitle($label);
        }

        return $label;
    }

    protected function searchForGroupLabelInConfig(string $groupKey): ?string
    {
        // TODO: Normalize this: sidebar_group_labels -> docs.sidebar.labels
        return $this->getConfigArray($this->generatesSidebar ? 'docs.sidebar_group_labels' : 'hyde.navigation.labels')[$groupKey] ?? null;
    }

    protected function searchForGroupPriorityInConfig(string $groupKey): ?int
    {
        return $this->getConfigArray($this->generatesSidebar ? 'docs.sidebar.order' : 'hyde.navigation.order')[$groupKey] ?? null;
    }

    /** @return array<string|int, string|int> */
    protected function getConfigArray(string $key): array
    {
        /** @var array<string|int, string|int> $array */
        $array = Config::getArray($key, []);

        return $array;
    }
}
