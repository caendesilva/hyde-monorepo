<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions\Internal;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

/**
 * @interal This class is used internally by the framework and is not part of the public API unless requested on GitHub.
 */
class FileFinder
{
    /**
     * @param  string|array<string>|false  $matchExtensions
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function handle(string $directory, string|array|false $matchExtensions = false, bool $recursive = false): Collection
    {
        if (! Filesystem::isDirectory($directory)) {
            return collect();
        }

        $finder = Finder::create()->files()->in(Hyde::path($directory));

        if ($recursive === false) {
            $finder->depth('== 0');
        }

        if ($matchExtensions !== false) {
            if (is_string($matchExtensions)) {
                $matchExtensions = array_map('trim', explode(',', $matchExtensions));
            }

            $finder->name('/\.('.implode('|', array_map(function (string $extension): string {
                return preg_quote(ltrim($extension, '.'), '/');
            }, $matchExtensions)).')$/i');
        }

        return collect($finder)->map(function (string $file): string {
            return Hyde::pathToRelative($file);
        })->sort()->values();
    }
}