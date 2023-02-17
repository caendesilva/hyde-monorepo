<?php

declare(strict_types=1);

namespace Hyde\Support\Contracts;

/**
 * Interface for discoverable HydePage classes.
 *
 * @property string $sourceDirectory (static)
 * @property string $outputDirectory (static)
 * @property string $fileExtension (static)
 */
interface DiscoverableContract
{
    public static function getSourceDirectory(): string;

    public static function getOutputDirectory(): string;

    public static function getFileExtension(): string;
}
