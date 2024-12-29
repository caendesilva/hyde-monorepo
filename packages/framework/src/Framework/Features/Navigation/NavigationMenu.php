<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

use function Hyde\evaluate_arrayable;

/**
 * Represents a site navigation menu, and contains all of its navigation items.
 *
 * The automatic navigation menus are stored within the service container and can be resolved by their identifiers.
 *
 * @example `$menu = app('navigation.main');` for the main navigation menu.
 * @example `$menu = app('navigation.sidebar');` for the documentation sidebar.
 *
 * @template T of NavigationItem|NavigationGroup
 */
class NavigationMenu
{
    public const DEFAULT = 500;
    public const LAST = 999;

    /** @var \Illuminate\Support\Collection<array-key, T> */
    protected Collection $items;

    /** @param  \Illuminate\Contracts\Support\Arrayable<array-key, T>|array<T>  $items */
    public function __construct(Arrayable|array $items = [])
    {
        $this->items = new Collection();

        /** @var array<T> $items */
        $items = evaluate_arrayable($items);

        $this->add($items);
    }

    /**
     * Get the navigation items in the menu.
     *
     * Items are automatically sorted by their priority, falling back to the order they were added.
     *
     * @return \Illuminate\Support\Collection<array-key, T>
     */
    public function getItems(): Collection
    {
        // The reason we sort them here is that navigation items can be added from different sources,
        // so any sorting we do in generator actions will only be partial. This way, we can ensure
        // that the items are always freshly sorted by their priorities when they are retrieved.

        return $this->items->sortBy(fn (NavigationItem|NavigationGroup $item) => $item->getPriority())->values();
    }

    /**
     * Add one or more navigation items to the navigation menu.
     *
     * @param  T|array<T>  $items
     */
    public function add(NavigationItem|NavigationGroup|array $items): static
    {
        /** @var T $item */
        foreach (Arr::wrap($items) as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    protected function addItem(NavigationItem|NavigationGroup $item): void
    {
        $this->items->push($item);
    }
}
