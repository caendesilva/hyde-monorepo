<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents a site navigation menu, and contains all of its navigation items.
 *
 * The automatic navigation menus are stored within the service container and can be resolved by their identifiers.
 *
 * @example `$menu = app('navigation.main');` for the main navigation menu.
 * @example `$menu = app('navigation.sidebar');` for the documentation sidebar.
 */
abstract class NavigationMenu
{
    public const DEFAULT = 500;
    public const LAST = 999;

    /** @var \Illuminate\Support\Collection<\Hyde\Framework\Features\Navigation\NavigationItem|\Hyde\Framework\Features\Navigation\NavigationGroup> */
    protected Collection $items;

    public function __construct(Arrayable|array $items = [])
    {
        $this->items = new Collection();

        foreach ($items as $item) {
            // Instead of adding the items directly, we iterate through them and
            // add them through the helper. This ensures that all the items are
            // of the correct type, bringing type safety to the menu's items.

            $this->add($item);
        }
    }

    /**
     * Get the navigation items in the menu.
     *
     * Items are automatically sorted by their priority, falling back to the order they were added.
     *
     * @return \Illuminate\Support\Collection<\Hyde\Framework\Features\Navigation\NavigationItem|\Hyde\Framework\Features\Navigation\NavigationGroup>
     */
    public function getItems(): Collection
    {
        // The reason we sort them here is that navigation items can be added from different sources,
        // so any sorting we do in generator actions will only be partial. This way, we can ensure
        // that the items are always freshly sorted by their priorities when they are retrieved.

        return $this->items->sortBy(fn (NavigationItem|NavigationGroup $item) => $item->getPriority())->values();
    }

    /**
     * Add a navigation item to the navigation menu.
     */
    public function add(NavigationItem|NavigationGroup $item): void
    {
        $this->items->push($item);
    }
}
