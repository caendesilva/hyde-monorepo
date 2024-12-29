<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use BadMethodCallException;
use InvalidArgumentException;

use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function in_array;
use function is_subclass_of;

/**
 * @internal Single-use trait for the HydeKernel class.
 *
 * @see \Hyde\Foundation\HydeKernel
 */
trait ManagesExtensions
{
    /**
     * Register a HydePHP extension within the HydeKernel.
     *
     * Typically, you would call this method in the register method of a service provider.
     * If your package uses the standard Laravel (Composer) package discovery feature,
     * the extension will automatically be enabled when the package is installed.
     *
     * @param  class-string<\Hyde\Foundation\Concerns\HydeExtension>  $extension
     */
    public function registerExtension(string $extension): void
    {
        if ($this->booted) {
            // We throw an exception here to prevent the developer from registering aa extension after the Kernel has been booted.
            // The reason we do this is because at this point all the source files have already been discovered and parsed.
            // If we allowed new classes after this point, we would have to reboot everything which adds complexity.

            throw new BadMethodCallException('Cannot register an extension after the Kernel has been booted.');
        }

        if (! is_subclass_of($extension, HydeExtension::class)) {
            // We want to make sure that the extension class extends the HydeExtension class,
            // so that we won't have to check the methods we need to call exist later on.

            throw new InvalidArgumentException("Extension [$extension] must extend the HydeExtension class.");
        }

        if (in_array($extension, $this->getRegisteredExtensions(), true)) {
            // While throwing an exception here is not required since we are using an associative array,
            // it may be helpful for the developer to know that their registration logic may be flawed.

            throw new InvalidArgumentException("Extension [$extension] is already registered.");
        }

        $instance = new $extension();
        $this->extensions[$extension] = $instance;

        $this->booting([$instance, 'booting']);
        $this->booted([$instance, 'booted']);
    }

    /**
     * Get the singleton instance of the specified extension.
     *
     * @template T of \Hyde\Foundation\Concerns\HydeExtension
     *
     * @param  class-string<T>  $extension
     * @return T
     */
    public function getExtension(string $extension): HydeExtension
    {
        if (! isset($this->extensions[$extension])) {
            throw new InvalidArgumentException("Extension [$extension] is not registered.");
        }

        return $this->extensions[$extension];
    }

    /**
     * Determine if the specified extension is registered.
     *
     * @param  class-string<\Hyde\Foundation\Concerns\HydeExtension>  $extension
     */
    public function hasExtension(string $extension): bool
    {
        return isset($this->extensions[$extension]);
    }

    /** @return array<\Hyde\Foundation\Concerns\HydeExtension> */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /** @return array<class-string<\Hyde\Foundation\Concerns\HydeExtension>> */
    public function getRegisteredExtensions(): array
    {
        return array_keys($this->extensions);
    }

    /** @return array<class-string<\Hyde\Pages\Concerns\HydePage>> */
    public function getRegisteredPageClasses(): array
    {
        return array_unique(array_merge(...array_map(function (string $extension): array {
            /** @var <class-string<\Hyde\Foundation\Concerns\HydeExtension>> $extension */
            return $extension::getPageClasses();
        }, $this->getRegisteredExtensions())));
    }
}
