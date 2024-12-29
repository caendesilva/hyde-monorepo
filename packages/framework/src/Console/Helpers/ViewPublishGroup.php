<?php

declare(strict_types=1);

namespace Hyde\Console\Helpers;

use Hyde\Facades\Filesystem;
use Hyde\Foundation\Providers\ViewServiceProvider;
use Hyde\Hyde;
use Illuminate\Support\Str;

use function Hyde\path_join;
use function Hyde\unslash;

/**
 * @internal Helper object for publishable view groups.
 */
class ViewPublishGroup
{
    public readonly string $group;

    public readonly string $name;
    public readonly string $description;

    public readonly string $source;
    public readonly string $target;

    /** @var array<string> The filenames relative to the source directory. */
    public readonly array $files;

    /** @var class-string<\Hyde\Foundation\Providers\ViewServiceProvider> */
    protected static string $provider = ViewServiceProvider::class;

    protected function __construct(string $group, string $source, string $target, array $files, ?string $name = null, ?string $description = null)
    {
        $this->group = $group;
        $this->source = $source;
        $this->target = $target;
        $this->files = $files;

        $this->name = $name ?? Hyde::makeTitle($group);
        $this->description = $description ?? "Publish the '$group' files for customization.";
    }

    public static function fromGroup(string $group, ?string $name = null, ?string $description = null): static
    {
        [$source, $target] = static::keyedArrayToTuple(static::$provider::pathsToPublish(static::$provider, $group));
        [$source, $target] = [static::normalizePath($source), static::normalizePath($target)];

        $files = static::findFiles($source);

        return new static($group, $source, $target, $files, $name, $description);
    }

    /** @return array<string, string> The source file paths mapped to their target file paths. */
    public function publishableFilesMap(): array
    {
        return collect($this->files)->mapWithKeys(fn (string $file): array => [
            path_join($this->source, $file) => path_join($this->target, $file),
        ])->all();
    }

    /**
     * @param  array<string, string>  $array
     * @return list<string>
     */
    protected static function keyedArrayToTuple(array $array): array
    {
        return [key($array), current($array)];
    }

    /** @return array<string> */
    protected static function findFiles(string $source): array
    {
        return Filesystem::findFiles($source, recursive: true)
            ->map(fn (string $file) => static::normalizePath($file))
            ->map(fn (string $file) => unslash(Str::after($file, $source)))
            ->sort(fn (string $a, string $b): int => substr_count($a, '/') <=> substr_count($b, '/') ?: strcmp($a, $b))
            ->all();
    }

    protected static function normalizePath(string $path): string
    {
        return Hyde::pathToRelative(
            Filesystem::exists($path) ? realpath($path) : $path
        );
    }
}
