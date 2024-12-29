<?php

declare(strict_types=1);

namespace Hyde\RealtimeCompiler\Actions;

/**
 * Locate a static file to proxy.
 */
class AssetFileLocator
{
    public static function find(string $path): ?string
    {
        $path = trim($path, '/');

        $file = BASE_PATH.'/_media/'.str_replace('media/', '', $path);

        return file_exists($file) ? $file : null;
    }
}
