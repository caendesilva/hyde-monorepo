<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Illuminate\Support\Str;

use function ltrim;
use function substr;
use function explode;
use function implode;
use function sprintf;
use function array_map;
use function preg_match;

/**
 * @internal This class contains shared helper code for the framework to provide numerical page ordering for documentation sidebars.
 *           It is not intended to be used outside the framework code internals.
 */
class NumericalPageOrderingHelper
{
    /** @var array<string> The delimiters that are used to separate the numerical prefix from the rest of the identifier. */
    protected const DELIMITERS = ['-', '_'];

    /** Determines if a given identifier has a numerical prefix. */
    public static function hasNumericalPrefix(string $identifier): bool
    {
        if (static::isIdentifierNested($identifier)) {
            $identifier = static::getCoreIdentifierPart($identifier);
        }

        return preg_match(sprintf('/^\d+[%s]/', implode(static::DELIMITERS)), $identifier) === 1;
    }

    /**
     * Splits a numbered identifier into its numerical prefix and the rest of the identifier.
     *
     * @return array{integer, string}
     */
    public static function splitNumericPrefix(string $identifier): array
    {
        if (static::isIdentifierNested($identifier)) {
            $parentPath = static::getNestedIdentifierPrefix($identifier);
            $identifier = static::getCoreIdentifierPart($identifier);
        }

        $separator = static::getFirstCharacterFromIdentifier($identifier);
        $parts = explode($separator, $identifier, 2);

        $parts[0] = (int) $parts[0];

        if (isset($parentPath)) {
            $parentPaths = explode('/', $parentPath);
            $parentPaths = array_map(function (string $part): string {
                return static::hasNumericalPrefix($part) ? static::splitNumericPrefix($part)[1] : $part;
            }, $parentPaths);
            $parentPath = implode('/', $parentPaths);

            $parts[1] = "$parentPath/$parts[1]";
        }

        return $parts;
    }

    protected static function isIdentifierNested(string $identifier): bool
    {
        return str_contains($identifier, '/');
    }

    protected static function getNestedIdentifierPrefix(string $identifier): string
    {
        return Str::beforeLast($identifier, '/');
    }

    protected static function getCoreIdentifierPart(string $identifier): string
    {
        return Str::afterLast($identifier, '/');
    }

    protected static function getFirstCharacterFromIdentifier(string $identifier): string
    {
        return substr(ltrim($identifier, '0123456789'), 0, 1);
    }
}
