<?php

declare(strict_types=1);

namespace Hyde\Markdown\Processing;

use Hyde\Hyde;
use Illuminate\Support\Str;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Markdown\Contracts\MarkdownPostProcessorContract;

class DynamicMarkdownLinkProcessor implements MarkdownPostProcessorContract
{
    /** @var array<string, \Hyde\Support\Filesystem\MediaFile>|null */
    protected static ?array $assetMapCache = null;

    public static function postprocess(string $html): string
    {
        foreach (static::routeMap() as $sourcePath => $route) {
            $patterns = [
                sprintf('<a href="%s"', $sourcePath),
                sprintf('<a href="/%s"', $sourcePath),
            ];

            $html = str_replace($patterns, sprintf('<a href="%s"', $route->getLink()), $html);
        }

        foreach (static::assetMap() as $sourcePath => $mediaFile) {
            $patterns = [
                sprintf('<img src="%s"', $sourcePath),
                sprintf('<img src="/%s"', $sourcePath),
            ];

            $html = str_replace($patterns, sprintf('<img src="%s"', static::assetPath($mediaFile)), $html);
        }

        return $html;
    }

    /** @return array<string, \Hyde\Support\Models\Route> */
    protected static function routeMap(): array
    {
        $map = [];

        /** @var \Hyde\Support\Models\Route $route */
        foreach (Hyde::routes() as $route) {
            $map[$route->getSourcePath()] = $route;
        }

        return $map;
    }

    /** @return array<string, \Hyde\Support\Filesystem\MediaFile> */
    protected static function assetMap(): array
    {
        if (static::$assetMapCache === null) {
            static::$assetMapCache = [];

            foreach (MediaFile::all() as $mediaFile) {
                static::$assetMapCache[$mediaFile->getPath()] = $mediaFile;
            }
        }

        return static::$assetMapCache;
    }

    protected static function assetPath(MediaFile $mediaFile): string
    {
        return Hyde::asset(Str::after($mediaFile->getPath(), '_media/'))->getLink();
    }

    /** @internal Testing helper to reset the asset map cache. */
    public static function resetAssetMapCache(): void
    {
        static::$assetMapCache = null;
    }
}
