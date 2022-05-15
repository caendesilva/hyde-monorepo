<?php

namespace Hyde\Framework\Concerns\Internal;

/**
 * Offloads file helper methods for the Hyde Facade.
 * 
 * If a method uses the name `path` it refers to an internal file path.
 * if a method uses the name `link` it refers to a web link used in Blade templates.
 *
 * @see \Hyde\Framework\Hyde
 */
trait FileHelpers
{
    /**
     * Get the subdirectory compiled documentation files are stored in.
     *
     * @return string
     */
    public static function docsDirectory(): string
    {
        return trim(config('hyde.docsDirectory', 'docs'), '/\\');
    }

    /**
     * Get the path to the frontpage for the documentation.
     *
     * @return string|false returns false if no frontpage is found
     */
    public static function docsIndexPath(): string|false
    {
        if (file_exists(static::path('_docs/index.md'))) {
            return static::docsDirectory().'/index.html';
        }

        if (file_exists(static::path('_docs/readme.md'))) {
            return static::docsDirectory().'/readme.html';
        }

        return false;
    }

    /**
     * Get an absolute file path from a supplied relative path.
     *
     * The function returns the fully qualified path to your site's root directory.
     *
     * You may also use the function to generate a fully qualified path to a given file
     * relative to the project root directory when supplying the path argument.
     *
     * @param  string  $path
     * @return string
     */
    public static function path(string $path = ''): string
    {
        if (empty($path)) {
            return static::getBasePath();
        }

        $path = trim($path, '/\\');

        return static::getBasePath().DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Works similarly to the path() function, but returns a file in the Framework package.
     *
     * @param  string  $path
     * @return string
     */
    public static function vendorPath(string $path = ''): string
    {
        return static::path('vendor/hyde/framework/'.trim($path, '/\\'));
    }

    /**
     * Format a link to an HTML file, allowing for pretty URLs, if enabled.
     * @see \Tests\Unit\HydeFileHelperForPageLinksCanCreatePrettyUrlsTest
     */
    public static function pageLink(string $destination): string
    {
        if (config('hyde.prettyUrls', false) === true) {
            if (str_ends_with($destination, '.html')) {
                return substr($destination, 0, -5);
            }
        }

        return $destination;
    }

    /**
     * Inject the proper number of `../` before the links in Blade templates.
     *
     * @param  string  $destination  the route to format
     * @param  string  $current  the current route
     * @return string
     */
    public static function relativeLink(string $destination, string $current = ''): string
    {
        $nestCount = substr_count($current, '/');
        $route = '';
        if ($nestCount > 0) {
            $route .= str_repeat('../', $nestCount);
        }
        $route .= $destination;

        return $route;
    }

    /**
     * Return a qualified URI path, if SITE_URL is set in .env, else return false.
     *
     * @param  string|null  $path  optional relative path suffix. Omit to return base url.
     * @return string|false
     */
    public static function uriPath(?string $path = ''): string|false
    {
        if (config('hyde.site_url', false)) {
            return rtrim(config('hyde.site_url'), '/').'/'.(trim($path, '/') ?? '');
        }

        return false;
    }

    /**
     * Wrapper for the copy function, but allows choosing if files may be overwritten.
     *
     * @param  string  $from  The source file path.
     * @param  string  $to  The destination file path.
     * @param  bool  $force  If true, existing files will be overwritten.
     * @return bool|int Returns true|false on copy() success|failure, or an error code on failure
     */
    public static function copy(string $from, string $to, bool $force = false): bool|int
    {
        if (! file_exists($from)) {
            return 404;
        }

        if (file_exists($to) && ! $force) {
            return 409;
        }

        return copy($from, $to);
    }
}
