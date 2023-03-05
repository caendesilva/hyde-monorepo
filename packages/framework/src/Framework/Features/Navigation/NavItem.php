<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Hyde;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Models\Route;
use Illuminate\Support\Str;
use Stringable;

/**
 * Abstraction for a navigation menu item. Used by the NavigationMenu and DocumentationSidebar classes.
 *
 * @todo Refactor to reduce code overlapping with the Route class
 *
 * You have a few options to construct a navigation menu item:
 *   1. You can supply a Route directly and explicit properties to the constructor
 *   2. You can use NavItem::fromRoute() to use data from the route
 *   3. You can use NavItem::toLink() for an external or un-routed link
 */
class NavItem implements Stringable
{
    /** @deprecated Use $destination instead */
    public Route $route;

    /** @deprecated Use $destination instead */
    public string $href;

    public string $destination;

    public string $label;
    public int $priority;

    /**
     * Create a new navigation menu item.
     */
    public function __construct(Route|string $destination, string $label, int $priority = 500)
    {
        $this->destination = $destination instanceof Route ? $destination->getLink() : $destination;

        // @deprecated: Temporary during refactor
        if ($destination instanceof Route) {
            $this->route = $destination;
        } else {
            $this->href = $destination;
        }

        $this->label = $label;
        $this->priority = $priority;
    }

    /**
     * Create a new navigation menu item from a route.
     */
    public static function fromRoute(Route $route): static
    {
        return new static(
            // $route->getLink(),
            $route, // needed by NavigationMenu::shouldItemBeHidden()
            $route->getPage()->navigationMenuLabel(),
            $route->getPage()->navigationMenuPriority()
        );
    }

    /**
     * Create a new navigation menu item leading to an external URI.
     */
    public static function toLink(string $href, string $label, int $priority = 500): static
    {
        return (new static($href, $label, $priority))->setDestination($href);
    }

    /**
     * Create a new navigation menu item leading to a Route model.
     */
    public static function toRoute(Route $route, string $label, int $priority = 500): static
    {
        return new static($route->getLink(), $label, $priority);
    }

    /**
     * Resolve a link to the navigation item.
     */
    public function __toString(): string
    {
        return $this->destination;
    }

    /**
     * Check if the NavItem instance is the current page.
     */
    public function isCurrent(?HydePage $current = null): bool
    {
        if ($current === null) {
            $current = Hyde::currentRoute()->getPage();
        }

        if (! isset($this->route)) {
            return ($current->getRoute()->getRouteKey() === $this->href)
            || ($current->getRoute()->getRouteKey().'.html' === $this->href);
        }

        return $current->getRoute()->getRouteKey() === $this->route->getRouteKey();
    }

    /** @deprecated Made obsolete by $destination */
    protected function setDestination(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    /** @todo Pre-resolve in constructor */
    public function getGroup(): ?string
    {
        return $this->normalizeGroupKey(($this->route ?? null)?->getPage()->data('navigation.group'));
    }

    protected function normalizeGroupKey(?string $group): ?string
    {
        return empty($group) ? null : Str::slug($group);
    }
}
