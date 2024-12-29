<?php

declare(strict_types=1);

namespace Hyde\Support;

use Hyde\Hyde;
use Hyde\Facades\Filesystem;
use Illuminate\Support\HtmlString;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Markdown\Models\Markdown;
use Illuminate\Support\Facades\Blade;

use function trim;
use function basename;

/**
 * The Includes facade provides a simple way to access partials in the includes directory.
 *
 * Both Markdown and Blade includes will be rendered to HTML.
 */
class Includes
{
    /**
     * @var string The directory where includes are stored.
     */
    protected static string $includesDirectory = 'resources/includes';

    /**
     * Return the path to the includes directory, or a partial within it, if requested.
     *
     * @param  string|null  $filename  The partial to return, or null to return the directory.
     * @return string Absolute Hyde::path() to the partial, or the includes directory.
     */
    public static function path(?string $filename = null): string
    {
        if ($filename === null) {
            return Hyde::path(static::$includesDirectory);
        }

        return Hyde::path(static::$includesDirectory.'/'.static::normalizePath($filename));
    }

    /**
     * Get the raw contents of a partial file in the includes directory.
     *
     * @param  string  $filename  The name of the partial file, including the extension.
     * @param  string|null  $default  The default value to return if the partial is not found.
     * @return string|null The raw contents of the partial file, or the default value if not found.
     */
    public static function get(string $filename, ?string $default = null): ?string
    {
        return static::getInclude(fn (string $contents): string => $contents, $filename, $default);
    }

    /**
     * Get the HTML contents of a partial file in the includes directory.
     *
     * @param  string  $filename  The name of the partial file, with or without the extension.
     * @param  string|null  $default  The default value to return if the partial is not found.
     * @return HtmlString|null The contents of the partial file, or the default value if not found.
     */
    public static function html(string $filename, ?string $default = null): ?HtmlString
    {
        return static::getInclude([static::class, 'renderHtml'], $filename, $default, '.html');
    }

    /**
     * Get the rendered Blade of a partial file in the includes directory.
     *
     * @param  string  $filename  The name of the partial file, with or without the extension.
     * @param  string|null  $default  The default value to return if the partial is not found.
     * @return HtmlString|null The rendered contents of the partial file, or the default value if not found.
     */
    public static function blade(string $filename, ?string $default = null): ?HtmlString
    {
        return static::getInclude([static::class, 'renderBlade'], $filename, $default, '.blade.php');
    }

    /**
     * Get the rendered Markdown of a partial file in the includes directory.
     *
     * @param  string  $filename  The name of the partial file, with or without the extension.
     * @param  string|null  $default  The default value to return if the partial is not found.
     * @return HtmlString|null The rendered contents of the partial file, or the default value if not found.
     */
    public static function markdown(string $filename, ?string $default = null): ?HtmlString
    {
        return static::getInclude([static::class, 'renderMarkdown'], $filename, $default, '.md');
    }

    /** @param callable(string): (\Illuminate\Support\HtmlString|string) $method */
    protected static function getInclude(callable $method, string $filename, ?string $default, string $extension = ''): HtmlString|string|null
    {
        $path = static::normalizePath($filename, $extension);
        $contents = static::getFileContents(static::path($path));

        if ($contents === null && $default === null) {
            return null;
        }

        return $method($contents ?? $default);
    }

    protected static function normalizePath(string $filename, string $extension = ''): string
    {
        return basename($filename, $extension).$extension;
    }

    protected static function getFileContents(string $path): ?string
    {
        if (! Filesystem::exists($path)) {
            return null;
        }

        return Filesystem::get($path);
    }

    protected static function renderHtml(string $html): HtmlString
    {
        return new HtmlString($html);
    }

    protected static function renderBlade(string $blade): HtmlString
    {
        return new HtmlString(Blade::render($blade));
    }

    protected static function renderMarkdown(string $markdown): HtmlString
    {
        return new HtmlString(trim(Markdown::render($markdown, MarkdownDocument::class)));
    }
}
