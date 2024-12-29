<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Framework\Features\Navigation\NavigationMenuConfigurationBuilder;

use function compact;

/**
 * General facade for navigation features.
 */
class Navigation
{
    /**
     * Configuration helper method to define a new navigation item, with better IDE support.
     *
     * The returned array will then be used by the framework to create a new NavigationItem instance. {@see \Hyde\Framework\Features\Navigation\NavigationItem}
     *
     * @see https://hydephp.com/docs/2.x/navigation-api
     *
     * @param  string<\Hyde\Support\Models\RouteKey>|string  $destination  Route key, or an external URI.
     * @param  string|null  $label  If not provided, Hyde will try to get it from the route's connected page, or from the URL.
     * @param  int|null  $priority  If not provided, Hyde will try to get it from the route or the default priority of 500.
     * @param  array<string, scalar>  $attributes  Additional attributes for the navigation item.
     * @return array{destination: string, label: ?string, priority: ?int, attributes: array<string, scalar>}
     */
    public static function item(string $destination, ?string $label = null, ?int $priority = null, array $attributes = []): array
    {
        return compact('destination', 'label', 'priority', 'attributes');
    }

    /**
     * Configuration helper method to define the navigation menu configuration with better IDE support.
     *
     * The builder is an array object that will be used by the framework to set the navigation menu configuration.
     *
     * @experimental This method is experimental and may change or be removed before the final release.
     */
    public static function configure(): NavigationMenuConfigurationBuilder
    {
        return new NavigationMenuConfigurationBuilder();
    }
}
