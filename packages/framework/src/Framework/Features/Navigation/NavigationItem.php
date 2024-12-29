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
 * Abstraction for a navigation menu item containing useful information like the destination, label, and priority.
 *
 * It is used by the MainNavigationMenu and DocumentationSidebar classes.
 */
class NavigationItem implements Stringable
{
    protected string|Route $destination;
    protected string $label;
    protected int $priority;

    /** @var array<string, scalar> */
    protected array $attributes = [];

    /**
     * Create a new navigation menu item, automatically filling in the properties from a Route instance if provided.
     *
     * @param  \Hyde\Support\Models\Route|string<\Hyde\Support\Models\RouteKey>|string  $destination  Route instance or route key, or an external URI.
     * @param  string|null  $label  If not provided, Hyde will try to get it from the route's connected page, or from the URL.
     * @param  int|null  $priority  If not provided, Hyde will try to get it from the route or the default priority of 500.
     * @param  array<string, scalar>  $attributes  Additional attributes for the navigation item.
     */
    public function __construct(Route|string $destination, ?string $label = null, ?int $priority = null, array $attributes = [])
    {
        [$this->destination, $this->label, $this->priority, $this->attributes] = self::make($destination, $label, $priority, $attributes);
    }

    /**
     * Create a new navigation menu item, automatically filling in the properties from a Route instance if provided.
     *
     * @param  \Hyde\Support\Models\Route|string<\Hyde\Support\Models\RouteKey>|string  $destination  Route instance or route key, or an external URI.
     * @param  string|null  $label  If not provided, Hyde will try to get it from the route's connected page, or from the URL.
     * @param  int|null  $priority  If not provided, Hyde will try to get it from the route or the default priority of 500.
     * @param  array<string, scalar>  $attributes  Additional attributes for the navigation item.
     */
    public static function create(Route|string $destination, ?string $label = null, ?int $priority = null, array $attributes = []): static
    {
        return new static(...self::make($destination, $label, $priority, $attributes));
    }

    /**
     * Resolve a link to the navigation item. See `getLink()` for more information.
     */
    public function __toString(): string
    {
        return $this->getLink();
    }

    /**
     * Resolve the destination link of the navigation item.
     *
     * This can then be used in the `href` attribute of an anchor tag.
     *
     * If the destination is a Route, it will be resolved using the Route's link.
     * Otherwise, it will be returned as is for external links using URLs.
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

    /** @return array{\Hyde\Support\Models\Route|string, string, int, array<string, scalar>} */
    protected static function make(Route|string $destination, ?string $label = null, ?int $priority = null, array $attributes = []): array
    {
        // Automatically resolve the destination if it's a route key.
        if (is_string($destination) && Routes::has($destination)) {
            $destination = Routes::get($destination);
        }

        if ($destination instanceof Route) {
            // Try to fill in missing properties from the route's connected page.
            $label ??= $destination->getPage()->navigationMenuLabel();
            $priority ??= $destination->getPage()->navigationMenuPriority();
        }

        return [$destination, $label ?? $destination, $priority ?? NavigationMenu::DEFAULT, $attributes];
    }

    /** @return array<string, scalar> */
    public function getExtraAttributes(): array
    {
        return $this->attributes;
    }
}
