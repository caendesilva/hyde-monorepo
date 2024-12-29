<?php

declare(strict_types=1);

namespace Hyde\Console\Helpers;

use Hyde\Facades\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @internal This class offloads logic from the PublishViewsCommand class and should not be used elsewhere.
 */
class InteractivePublishCommandHelper
{
    /** @var array<string, string> Map of source files to target files */
    protected array $publishableFilesMap;

    protected readonly int $originalFileCount;

    /** @param  array<string, string>  $publishableFilesMap */
    public function __construct(array $publishableFilesMap)
    {
        $this->publishableFilesMap = $publishableFilesMap;
        $this->originalFileCount = count($publishableFilesMap);
    }

    /** @return array<string, string> */
    public function getFileChoices(): array
    {
        return Arr::mapWithKeys($this->publishableFilesMap, /** @return array<string, string> */ function (string $target, string $source): array {
            return [$source => $this->pathRelativeToDirectory($source, $this->getBaseDirectory())];
        });
    }

    /**
     * Only publish the selected files.
     *
     * @param  array<string>  $selectedFiles  Array of selected file paths, matching the keys of the publishableFilesMap.
     */
    public function only(array $selectedFiles): void
    {
        $this->publishableFilesMap = Arr::only($this->publishableFilesMap, $selectedFiles);
    }

    /** Find the most specific common parent directory path for the files, trimming as much as possible whilst keeping specificity and uniqueness. */
    public function getBaseDirectory(): string
    {
        $partsMap = collect($this->publishableFilesMap)->map(function (string $file): array {
            return explode('/', $file);
        });

        $commonParts = $partsMap->reduce(function (array $carry, array $parts): array {
            return array_intersect($carry, $parts);
        }, $partsMap->first());

        return implode('/', $commonParts);
    }

    public function publishFiles(): void
    {
        foreach ($this->publishableFilesMap as $source => $target) {
            Filesystem::ensureDirectoryExists(dirname($target));
            Filesystem::copy($source, $target);
        }
    }

    public function formatOutput(string $group): string
    {
        $fileCount = count($this->publishableFilesMap);
        $publishedOneFile = $fileCount === 1;
        $publishedAllGroups = $group === 'all';
        $publishedAllFiles = $fileCount === $this->originalFileCount;
        $selectedFilesModifier = $publishedAllFiles ? 'all' : 'selected';

        return match (true) {
            $publishedAllGroups => sprintf('Published all %d files to [%s]', $fileCount, $this->getBaseDirectory()),
            $publishedOneFile => sprintf('Published selected file to [%s]', reset($this->publishableFilesMap)),
            default => sprintf('Published %s [%s] files to [%s]', $selectedFilesModifier, Str::singular($group), $this->getBaseDirectory())
        };
    }

    protected function pathRelativeToDirectory(string $source, string $directory): string
    {
        return Str::after($source, basename($directory).'/');
    }
}
