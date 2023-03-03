<?php

declare(strict_types=1);

namespace Hyde\Support;

use Hyde\Facades\Filesystem;
use Hyde\Framework\Actions\MarkdownFileParser;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Automatically generates Laravel Collections from static data files,
 * such as Markdown components and YAML files using Hyde Autodiscovery.
 *
 * This class acts both as a base collection class, a factory for
 * creating collections, and static facade shorthand helper methods.
 *
 * The static "facade" methods are what makes this class special,
 * they allow you to quickly access the data collections.
 *
 * To use them retrieve a collection, call a facade method with the
 * name of the data collection subdirectory directory.
 *
 * All collections are keyed by their filename which is relative
 * to the configured data collection source directory.
 */
class DataCollection extends Collection
{
    use InteractsWithDirectories;

    /**
     * The base directory for all data collections. Can be modified using a service provider.
     */
    public static string $sourceDirectory = 'resources/collections';

    /**
     * Get a collection of Markdown documents in the resources/collections/<$key> directory.
     * Each Markdown file will be parsed into a MarkdownDocument with front matter.
     *
     * @return DataCollection<string, \Hyde\Markdown\Models\MarkdownDocument>
     */
    public static function markdown(string $name): static
    {
        static::needsDirectory(static::$sourceDirectory);

        return new static(static::findMarkdownFiles($name)->mapWithKeys(function (string $file): array {
            return [static::makeIdentifier($file) => (new MarkdownFileParser($file))->get()];
        }));
    }

    /**
     * Get a collection of YAML documents in the resources/collections/<$key> directory.
     * Each YAML file will be parsed into a FrontMatter object.
     *
     * @return DataCollection<string, \Hyde\Markdown\Models\FrontMatter>
     */
    public static function yaml(string $name): static
    {
        static::needsDirectory(static::$sourceDirectory);

        return new static(static::findYamlFiles($name)->mapWithKeys(function (string $file): array {
            return [static::makeIdentifier($file) => (new MarkdownFileParser($file))->get()->matter()];
        }));
    }

    /**
     * Get a collection of JSON documents in the resources/collections/<$key> directory.
     * Each JSON file will be parsed into a stdClass object.
     *
     * @return DataCollection<string, \stdClass>
     */
    public static function json(string $name): static
    {
        static::needsDirectory(static::$sourceDirectory);

        return new static(static::findJsonFiles($name)->mapWithKeys(function (string $file): array {
            return [static::makeIdentifier($file) => json_decode(Filesystem::get($file))];
        }));
    }

    protected static function findMarkdownFiles(string $name): Collection
    {
        return Filesystem::smartGlob(static::$sourceDirectory."/$name/*.md");
    }

    protected static function findYamlFiles(string $name): Collection
    {
        return Filesystem::smartGlob(static::$sourceDirectory."/$name/*.{yaml,yml}", GLOB_BRACE);
    }

    protected static function findJsonFiles(string $name): Collection
    {
        return Filesystem::smartGlob(static::$sourceDirectory."/$name/*.json");
    }

    protected static function makeIdentifier(string $path): string
    {
        return unslash(Str::after($path, static::$sourceDirectory));
    }
}
