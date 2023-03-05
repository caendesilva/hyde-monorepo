<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Facades\Config;
use Hyde\Support\Models\Route;
use Hyde\Foundation\Facades\Routes;
use Illuminate\Support\Collection;
use function collect;

/**
 * @see \Hyde\Framework\Testing\Feature\NavigationMenuTest
 */
abstract class BaseNavigationMenu
{
    public Collection $items;

    /** @todo Consider protecting to scope down public API */
    final public function __construct()
    {
        $this->items = new Collection();
    }

    public static function create(): static
    {
        // Todo: Simplify call order by making chained protected methods void
        $menu = new static();
        $menu
            ->generate()
            ->removeDuplicateItems()
            ->sortByPriority();

        return $menu;
    }

    /** @deprecated Will be made protected */
    public function generate(): static
    {
        Routes::each(function (Route $route): void {
            if ($this->canAddRoute($route)) {
                $this->items->put($route->getRouteKey(), NavItem::fromRoute($route));
            }
        });

        collect(Config::getArray('hyde.navigation.custom', []))->each(function (NavItem $item): void {
            // Since these were added explicitly by the user, we can assume they should always be shown
            $this->items->push($item);
        });

        return $this;
    }

    protected function canAddRoute(Route $route): bool
    {
        return $route->getPage()->showInNavigation();
    }

    protected function removeDuplicateItems(): static
    {
        $this->items = $this->items->unique(function (NavItem $item): string {
            // Filter using a combination of the group and label to allow duplicate labels in different groups
            return $item->getGroup().$item->label;
        });

        return $this;
    }

    protected function sortByPriority(): static
    {
        $this->items = $this->items->sortBy('priority')->values();

        return $this;
    }
}
