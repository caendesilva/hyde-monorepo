<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Foundation\Facades\Routes;
use Hyde\Hyde;
use Hyde\Support\Models\Route;
use Illuminate\Support\Str;
use Stringable;
use Hyde\Support\Models\ExternalRoute;

/**
 * Abstraction for a navigation menu item. Used by the MainNavigationMenu and DocumentationSidebar classes.
 *
 * You have a few options to construct a navigation menu item:
 *   1. You can supply a Route directly and explicit properties to the constructor
 *   2. You can use NavItem::fromRoute() to use data from the route
 *   3. You can use NavItem::forLink() for an external or un-routed link
 */
class NavItem implements Stringable
{
    public readonly Route $destination;
    public readonly string $label;
    public readonly int $priority;
    public readonly ?string $group;

    /** @var array<\Hyde\Framework\Features\Navigation\NavItem> */
    public readonly array $children;

    /**
     * Create a new navigation menu item.
     */
    public function __construct(Route|string $destination, string $label, int $priority = 500, ?string $group = null, array $children = [])
    {
        if (is_string($destination)) {
            $destination = Routes::get($destination) ?? new ExternalRoute($destination);
        }

        $this->destination = $destination;
        $this->label = $label;
        $this->priority = $priority;
        $this->group = $group;
        $this->children = $children;
    }

    /**
     * Create a new navigation menu item from a route.
     */
    public static function fromRoute(Route $route, ?string $label = null, ?int $priority = null, ?string $group = null): static
    {
        return new static(
            $route,
            $label ?? $route->getPage()->navigationMenuLabel(),
            $priority ?? $route->getPage()->navigationMenuPriority(),
            $group ?? static::getRouteGroup($route),
        );
    }

    /**
     * Create a new navigation menu item leading to an external URI.
     */
    public static function forLink(string $href, string $label, int $priority = 500): static
    {
        return new static($href, $label, $priority);
    }

    /**
     * Create a new navigation menu item leading to a Route model.
     *
     * @param  \Hyde\Support\Models\Route|string<\Hyde\Support\Models\RouteKey>  $route  Route model or route key
     * @param  int|null  $priority  Leave blank to use the priority of the route's corresponding page.
     * @param  string|null  $label  Leave blank to use the label of the route's corresponding page.
     * @param  string|null  $group  Leave blank to use the group of the route's corresponding page.
     */
    public static function forRoute(Route|string $route, ?string $label = null, ?int $priority = null, ?string $group = null): static
    {
        return static::fromRoute($route instanceof Route ? $route : Routes::getOrFail($route), $label, $priority, $group);
    }

    /**
     * Resolve a link to the navigation item.
     */
    public function __toString(): string
    {
        return $this->getLink();
    }

    /**
     * Get the destination route of the navigation item.
     */
    public function getDestination(): Route
    {
        return $this->destination;
    }

    /**
     * Resolve the destination link of the navigation item.
     */
    public function getLink(): string
    {
        return (string) $this->destination;
    }

    /**
     * Get the label of the navigation item.
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get the priority to determine the order of the navigation item.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the group identifier of the navigation item, if any.
     *
     * For sidebars this is the category key, for navigation menus this is the dropdown key.
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * Check if the NavItem instance is the current page.
     */
    public function isCurrent(): bool
    {
        return Hyde::currentRoute()->getLink() === (string) $this->destination;
    }

    /**
     * Get the children of the navigation item.
     *
     * For the main navigation menu, this stores any dropdown items.
     *
     * @return array<\Hyde\Framework\Features\Navigation\NavItem>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    protected static function getRouteGroup(Route $route): ?string
    {
        /** @var string|null $group */
        $group = $route->getPage()->data('navigation.group');

        return static::normalizeGroupKey($group);
    }

    protected static function normalizeGroupKey(?string $group): ?string
    {
        return $group ? Str::slug($group) : null;
    }
}
