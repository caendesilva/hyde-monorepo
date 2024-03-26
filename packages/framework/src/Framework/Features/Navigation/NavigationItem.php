<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Hyde\Pages\Concerns\HydePage;
use Hyde\Foundation\Facades\Routes;
use Hyde\Hyde;
use Hyde\Support\Models\Route;
use Stringable;

use function is_string;

/**
 * Abstraction for a navigation menu item. Used by the MainNavigationMenu and DocumentationSidebar classes.
 */
class NavigationItem implements Stringable
{
    protected string|Route $destination;
    protected string $label;
    protected int $priority;

    public function __construct(Route|string $destination, string $label, int $priority = NavigationMenu::DEFAULT)
    {
        $this->label = $label;
        $this->priority = $priority;
        $this->destination = $destination;
    }

    /**
     * Create a new navigation menu item, automatically filling in the properties from a Route instance if provided.
     *
     * @param  \Hyde\Support\Models\Route|string<\Hyde\Support\Models\RouteKey>|string  $destination  Route instance or route key, or external URI.
     * @param  string|null  $label  Leave blank to use the label of the route's corresponding page, if there is one tied to the route.
     * @param  int|null  $priority  Leave blank to use the priority of the route's corresponding page, if there is one tied to the route.
     */
    public static function create(Route|string $destination, ?string $label = null, ?int $priority = null): static
    {
        return new static(...self::make($destination, $label, $priority));
    }

    /**
     * Resolve a link to the navigation item.
     */
    public function __toString(): string
    {
        return $this->getLink();
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
     * If the navigation item is a link to a routed page, get the corresponding page instance.
     */
    public function getPage(): ?HydePage
    {
        return $this->destination instanceof Route ? $this->destination->getPage() : null;
    }

    /**
     * Check if the NavigationItem instance is the current page being rendered.
     */
    public function isActive(): bool
    {
        return Hyde::currentRoute()?->getLink() === $this->getLink();
    }

    protected static function make(Route|string $destination, ?string $label = null, ?int $priority = null): array
    {
        if (is_string($destination) && Routes::has($destination)) {
            $destination = Routes::get($destination);
        }

        if ($destination instanceof Route) {
            $label ??= $destination->getPage()->navigationMenuLabel();
            $priority ??= $destination->getPage()->navigationMenuPriority();
        }

        return [$destination, $label ?? $destination, $priority ?? NavigationMenu::DEFAULT];
    }
}
