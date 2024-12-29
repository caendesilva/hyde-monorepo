<?php

declare(strict_types=1);

namespace Hyde\Foundation\Kernel;

use Hyde\Hyde;
use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\PharSupport;
use Hyde\Foundation\Concerns\HasMediaFiles;
use Illuminate\Support\Collection;
use Hyde\Framework\Actions\Internal\FileFinder;

use function collect;
use function Hyde\normalize_slashes;
use function Hyde\path_join;
use function file_exists;
use function str_replace;
use function array_map;
use function is_array;
use function str_starts_with;
use function Hyde\unslash;
use function unlink;
use function touch;

/**
 * File helper methods, bound to the HydeKernel instance, and is an integral part of the framework.
 *
 * All paths arguments are relative to the root of the application,
 * and will be automatically resolved to absolute paths.
 */
class Filesystem
{
    use HasMediaFiles;

    protected HydeKernel $kernel;

    public function __construct(HydeKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getBasePath(): string
    {
        return $this->kernel->getBasePath();
    }

    /**
     * Get an absolute file path from a supplied relative path.
     *
     * The function returns the fully qualified path to your site's root directory.
     *
     * You may also use the function to generate a fully qualified path to a given file
     * relative to the project root directory when supplying the path argument.
     */
    public function path(string $path = ''): string
    {
        if (empty($path)) {
            return $this->getBasePath();
        }

        if (str_starts_with($path, 'phar://')) {
            return $path;
        }

        $path = unslash($this->pathToRelative($path));

        return path_join($this->getBasePath(), $path);
    }

    /**
     * Get an absolute file path from a supplied relative path.
     *
     * Input types are matched, meaning that if the input is a string so will the output be.
     *
     * @param  string|array<string>  $path
     * @return ($path is string ? string : array<string>)
     */
    public function pathToAbsolute(string|array $path): string|array
    {
        if (is_array($path)) {
            return array_map(fn (string $path): string => $this->pathToAbsolute($path), $path);
        }

        return $this->path($path);
    }

    /**
     * Decode an absolute path created with a Hyde::path() helper into its relative counterpart.
     */
    public function pathToRelative(string $path): string
    {
        return normalize_slashes(str_starts_with($path, $this->path())
            ? unslash(str_replace($this->path(), '', $path))
            : $path);
    }

    /**
     * Get the absolute path to the compiled site directory, or a file within it.
     */
    public function sitePath(string $path = ''): string
    {
        if (empty($path)) {
            return $this->path(Hyde::getOutputDirectory());
        }

        return $this->path(path_join(Hyde::getOutputDirectory(), unslash($path)));
    }

    /**
     * Works similarly to the path() function, but returns a file in the Framework package.
     *
     * @internal This is not intended to be used outside the HydePHP framework.
     */
    public function vendorPath(string $path = '', string $package = 'framework'): string
    {
        if (PharSupport::running() && ! PharSupport::hasVendorDirectory()) {
            return PharSupport::vendorPath($path, $package);
        }

        return $this->path("vendor/hyde/$package/".unslash($path));
    }

    /**
     * Touch one or more files in the project's directory.
     *
     * @param  string|array<string>  $path
     */
    public function touch(string|array $path): bool
    {
        return collect($path)->map(function (string $path): bool {
            return touch($this->path($path));
        })->contains(false) === false;
    }

    /**
     * Unlink one or more files in the project's directory.
     *
     * @param  string|array<string>  $path
     */
    public function unlink(string|array $path): bool
    {
        return collect($path)->map(function (string $path): bool {
            return unlink($this->path($path));
        })->contains(false) === false;
    }

    /**
     * Unlink a file in the project's directory, but only if it exists.
     */
    public function unlinkIfExists(string $path): bool
    {
        return file_exists($this->path($path)) && unlink($this->path($path));
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function smartGlob(string $pattern, int $flags = 0): Collection
    {
        /** @var \Illuminate\Support\Collection<int, string> $files */
        $files = collect(\Hyde\Facades\Filesystem::glob($pattern, $flags));

        return $files->map(fn (string $path): string => $this->pathToRelative($path));
    }

    /**
     * @param  string|array<string>|false  $matchExtensions
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function findFiles(string $directory, string|array|false $matchExtensions = false, bool $recursive = false): Collection
    {
        /** @var \Hyde\Framework\Actions\Internal\FileFinder $finder */
        $finder = app(FileFinder::class);

        return $finder->handle($directory, $matchExtensions, $recursive);
    }
}
