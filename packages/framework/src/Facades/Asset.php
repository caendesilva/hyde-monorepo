<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Support\Filesystem\MediaFile;

/**
 * Simplified facade to interact with media files.
 *
 * @see \Hyde\Support\Filesystem\MediaFile
 */
class Asset
{
    /**
     * Get a MediaFile instance for the given filename in the media source directory.
     *
     * @throws \Hyde\Framework\Exceptions\FileNotFoundException If the file does not exist in the `_media` source directory.
     */
    public static function get(string $file): MediaFile
    {
        return MediaFile::get($file);
    }

    /**
     * Check if a media file exists in the source directory.
     */
    public static function exists(string $file): bool
    {
        return Filesystem::exists(MediaFile::sourcePath($file));
    }
}
