<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use TypeError;
use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Configuration helper class to define the navigation menu configuration with better IDE support.
 *
 * The configured data will then be used by the framework to set the navigation menu configuration.
 *
 * @see \Hyde\Facades\Navigation::configure()
 *
 * @experimental This class is experimental and may change or be removed before the final release.
 */
class NavigationMenuConfigurationBuilder extends ArrayObject implements Arrayable
{
    /**
     * Set the order of the navigation items.
     *
     * Either define a map of route keys to priorities, or just a list of route keys and we'll try to match that order.
     *
     * @param  array<string, int>|array<string>  $order
     * @return $this
     */
    public function setPagePriorities(array $order): static
    {
        $this['order'] = $order;

        return $this;
    }

    /**
     * Set the labels for the navigation items.
     *
     * Each key should be a route key, and the value should be the label to display.
     *
     * @param  array<string, string>  $labels
     * @return $this
     */
    public function setPageLabels(array $labels): static
    {
        $this['labels'] = $labels;

        return $this;
    }

    /**
     * Exclude certain items from the navigation.
     *
     * Each item should be a route key for the page to exclude.
     *
     * @param  array<string>  $exclude
     * @return $this
     */
    public function excludePages(array $exclude): static
    {
        $this['exclude'] = $exclude;

        return $this;
    }

    /**
     * Add custom items to the navigation.
     *
     * @example `[Navigation::item('https://github.com/hydephp/hyde', 'GitHub', 200, ['target' => '_blank'])]`
     *
     * @param  array<array{destination: string, label: ?string, priority: ?int, attributes: array<string, scalar>}>  $custom
     * @return $this
     */
    public function addNavigationItems(array $custom): static
    {
        $this['custom'] = $custom;

        return $this;
    }

    /**
     * Set the display mode for pages in subdirectories.
     *
     * You can choose between 'dropdown', 'flat', and 'hidden'. The default is 'hidden'.
     *
     * @param  'dropdown'|'flat'|'hidden'  $displayMode
     * @return $this
     */
    public function setSubdirectoryDisplayMode(string $displayMode): static
    {
        self::assertType(['dropdown', 'flat', 'hidden'], $displayMode);

        $this['subdirectory_display'] = $displayMode;

        return $this;
    }

    /**
     * Hide pages in subdirectories from the navigation.
     *
     * @experimental This method is experimental and may change or be removed before the final release.
     *
     * @return $this
     */
    public function hideSubdirectoriesFromNavigation(): static
    {
        return $this->setSubdirectoryDisplayMode('hidden');
    }

    /**
     * Get the instance as an array.
     *
     * @return array{order: array<string, int>, labels: array<string, string>, exclude: array<string>, custom: array<array{destination: string, label: ?string, priority: ?int, attributes: array<string, scalar>}>, subdirectory_display: string}
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /** @experimental May be moved to a separate helper class in the future. */
    protected static function assertType(array $types, string $value): void
    {
        if (! in_array($value, $types)) {
            throw new TypeError('Value must be one of: '.implode(', ', $types));
        }
    }
}
