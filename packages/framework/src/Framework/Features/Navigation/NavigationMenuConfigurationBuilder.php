<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use TypeError;
use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Configuration helper class to define the navigation menu configuration with better IDE support.
 *
 * The configured object will be cast to an array that will be used by the framework to set the config data.
 *
 * @experimental This class is experimental and may change in the future.
 */
class NavigationMenuConfigurationBuilder extends ArrayObject implements Arrayable
{
    /**
     * Set the order of the navigation items.
     *
     * @param  array<string, int>|array<string>  $order
     * @return $this
     */
    public function order(array $order): static
    {
        $this['order'] = $order;

        return $this;
    }

    /**
     * Set the labels for the navigation items.
     *
     * @param  array<string, string>  $labels
     * @return $this
     */
    public function labels(array $labels): static
    {
        $this['labels'] = $labels;

        return $this;
    }

    /**
     * Exclude certain items from the navigation.
     *
     * @param  array<string>  $exclude
     * @return $this
     */
    public function exclude(array $exclude): static
    {
        $this['exclude'] = $exclude;

        return $this;
    }

    /**
     * Add custom items to the navigation.
     *
     * @param  array<array{destination: string, label: ?string, priority: ?int, attributes: array<string, scalar>}>  $custom
     * @return $this
     */
    public function custom(array $custom): static
    {
        $this['custom'] = $custom;

        return $this;
    }

    /**
     * Set the display mode for subdirectories.
     *
     * @param  'dropdown'|'flat'|'hidden'  $displayMode
     * @return $this
     */
    public function subdirectoryDisplay(string $displayMode): static
    {
        self::assertType(['dropdown', 'flat', 'hidden'], $displayMode);

        $this['subdirectory_display'] = $displayMode;

        return $this;
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

    /** @internal */
    protected static function assertType(array $types, string $value): void
    {
        if (! in_array($value, $types)) {
            throw new TypeError('Value must be one of: '.implode(', ', $types));
        }
    }
}
