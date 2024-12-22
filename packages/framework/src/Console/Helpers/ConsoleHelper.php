<?php

declare(strict_types=1);

namespace Hyde\Console\Helpers;

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
}
