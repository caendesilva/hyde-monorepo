<?php

declare(strict_types=1);

namespace Hyde\Console\Helpers;

use Closure;
use Illuminate\Support\Collection;

use function Laravel\Prompts\multiselect;

/**
 * @internal This class contains internal helpers for interacting with the console, and for easier testing.
 *
 * @codeCoverageIgnore This class provides internal testing helpers and does not need to be tested.
 */
class ConsoleHelper
{
    protected static array $mocks = [];

    public static function clearMocks(): void
    {
        static::$mocks = [];
    }

    public static function usesWindowsOs()
    {
        if (isset(static::$mocks['usesWindowsOs'])) {
            return static::$mocks['usesWindowsOs'];
        }

        return windows_os();
    }

    public static function mockWindowsOs(bool $isWindows = true): void
    {
        static::$mocks['usesWindowsOs'] = $isWindows;
    }

    // Todo: Add test to ensure the signature is the same if Laravel updates it
    public static function multiselect(string $label, array|Collection $options, array|Collection $default = [], int $scroll = 5, bool|string $required = false, mixed $validate = null, string $hint = 'Use the space bar to select options.', ?Closure $transform = null): array
    {
        if (isset(static::$mocks['multiselect'])) {
            $returns = static::$mocks['multiselect'];
            $assertionCallback = static::$mocks['multiselectAssertion'] ?? null;

            if ($assertionCallback !== null) {
                $assertionCallback($label, $options, $default, $scroll, $required, $validate, $hint, $transform);
            }

            return $returns;
        }

        return multiselect($label, $options, $default, $scroll, $required, $validate, $hint, $transform);
    }

    public static function mockMultiselect(array $returns, ?Closure $assertionCallback = null): void
    {
        assert(! isset(static::$mocks['multiselect']), 'Cannot mock multiselect twice.');

        static::$mocks['multiselect'] = $returns;
        static::$mocks['multiselectAssertion'] = $assertionCallback;
    }
}
