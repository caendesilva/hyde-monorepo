<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Vite facade for handling Vite-related operations.
 */
class Vite
{
    protected const CSS_EXTENSIONS = ['css', 'less', 'sass', 'scss', 'styl', 'stylus', 'pcss', 'postcss'];
    protected const JS_EXTENSIONS = ['js', 'jsx', 'ts', 'tsx'];

    public static function running(): bool
    {
        return Filesystem::exists('app/storage/framework/cache/vite.hot');
    }

    public static function asset(string $path): HtmlString
    {
        return static::assets([$path]);
    }

    /** @param array<string> $paths */
    public static function assets(array $paths): HtmlString
    {
        $html = '<script src="http://localhost:5173/@vite/client" type="module"></script>';

        foreach ($paths as $path) {
            $html .= static::formatAssetPath($path);
        }

        return new HtmlString($html);
    }

    /** @throws InvalidArgumentException If the asset type is not supported. */
    protected static function formatAssetPath(string $path): string
    {
        if (static::isCssPath($path)) {
            return static::formatStylesheetLink($path);
        }

        if (static::isJsPath($path)) {
            return static::formatScriptInclude($path);
        }

        // We don't know how to handle other asset types, so we throw an exception to let the user know.
        throw new InvalidArgumentException("Unsupported asset type for path: '$path'");
    }

    protected static function isCssPath(string $path): bool
    {
        return static::checkFileExtensionForPath($path, static::CSS_EXTENSIONS);
    }

    protected static function isJsPath(string $path): bool
    {
        return static::checkFileExtensionForPath($path, static::JS_EXTENSIONS);
    }

    protected static function checkFileExtensionForPath(string $path, array $extensions): bool
    {
        return preg_match('/\.('.implode('|', $extensions).')$/', $path) === 1;
    }

    protected static function formatStylesheetLink(string $path): string
    {
        return sprintf('<link rel="stylesheet" href="http://localhost:5173/%s">', $path);
    }

    protected static function formatScriptInclude(string $path): string
    {
        return sprintf('<script src="http://localhost:5173/%s" type="module"></script>', $path);
    }
}
