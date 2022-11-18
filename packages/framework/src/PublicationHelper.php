<?php

declare(strict_types=1);

namespace Hyde;

use Carbon\Carbon;
use Hyde\Foundation\HydeKernel;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Rgasch\Collection\Collection;
use function Safe\file_get_contents;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class PublicationHelper
{
    /**
     * Ask for a CLI input value until we pass validation rules.
     *
     * @param  Command  $command
     * @param  string  $name
     * @param  string  $message
     * @param  array  $rules
     * @param  array  $rules
     * @return mixed $default
     */
    public static function askWithValidation(Command $command, string $name, string $message, Collection|array $rules = [], mixed $default = null)
    {
        if ($rules instanceof Collection) {
            $rules = $rules->toArray();
        }

        $answer = $command->ask($message, $default);
        $factory = app(ValidationFactory::class);
        $validator = $factory->make([$name => $answer], [$name => $rules]);

        if ($validator->passes()) {
            return $answer;
        }

        foreach ($validator->errors()->all() as $error) {
            $command->error($error);
        }

        return self::askWithValidation($command, $name, $message, $rules);
    }

    /**
     * Get the available HydeKernel instance.
     *
     * @return \Hyde\Foundation\HydeKernel
     */
    public static function getKernel(): HydeKernel
    {
        return app(HydeKernel::class);
    }

    /**
     * Format the publication type name to a suitable representation for file storage.
     *
     * @param  string  $pubTypeNameRaw
     * @return string
     */
    public static function formatNameForStorage(string $pubTypeNameRaw)
    {
        return Str::slug($pubTypeNameRaw);
    }

    /**
     * Return a collection of all defined publication types, indexed by the directory name.
     *
     * @return Collection<string, PublicationType>
     *
     * @throws \Exception
     */
    public static function getPublicationTypes(): Collection
    {
        $root = Hyde::path();
        $schemaFiles = glob("$root/*/schema.json", GLOB_BRACE);

        $pubTypes = Collection::create();
        foreach ($schemaFiles as $schemaFile) {
            $publicationType = new PublicationType($schemaFile);
            $pubTypes->{$publicationType->directory} = $publicationType;
        }

        return $pubTypes;
    }

    /**
     * Return all publications for a given pub type, optionally sorted by the publication's sortField.
     *
     * @param  Collection  $pubType
     * @return Collection
     *
     * @throws \Safe\Exceptions\FilesystemException
     */
    public static function getPublicationsForPubType(Collection $pubType, $sort = true): Collection
    {
        $root = base_path();
        $files = glob("$root/{$pubType->directory}/*.md");

        $publications = Collection::create();
        foreach ($files as $file) {
            $publications->add(self::getPublicationData($file));
        }

        if ($sort) {
            return $publications->sortBy(function ($publication) use ($pubType) {
                return $publication->matter->{$pubType->sortField};
            });
        }

        return $publications;
    }

    /**
     * Return all media items for a given publication type.
     *
     * @param  Collection  $pubType
     * @return Collection
     *
     * @throws \Safe\Exceptions\FilesystemException
     */
    public static function getMediaForPubType(Collection $pubType, $sort = true): Collection
    {
        $root = base_path();
        $files = glob("$root/_media/{$pubType->directory}/*.{jpg,jpeg,png,gif,pdf}", GLOB_BRACE);

        $media = Collection::create();
        foreach ($files as $file) {
            $media->add($file);
        }

        if ($sort) {
            return $media->sort()->values();
        }

        return $media;
    }

    /**
     * Read an MD file and return the parsed data.
     *
     * @param  string  $fileData
     * @return Collection
     */
    public static function getPublicationData(string $mdFileName): Collection
    {
        $fileData = file_get_contents($mdFileName);
        if (! $fileData) {
            throw new \Exception("No data read from [$mdFileName]");
        }

        $parsedFileData = YamlFrontMatter::markdownCompatibleParse($fileData);
        $matter = $parsedFileData->matter();
        $markdown = $parsedFileData->body();
        $matter['__slug'] = basename($mdFileName, '.md');
        $matter['__createdDatetime'] = Carbon::createFromTimestamp($matter['__createdAt']);

        return Collection::create(['matter' => $matter, 'markdown' => $markdown]);
    }

    /**
     * Check whether a given publication type exists.
     *
     * @param  string  $pubTypeName
     * @param  bool  $isRaw
     * @return bool
     *
     * @throws \Exception
     */
    public static function publicationTypeExists(string $pubTypeName, bool $isRaw = true): bool
    {
        if ($isRaw) {
            $pubTypeName = self::formatNameForStorage($pubTypeName);
        }

        return self::getPublicationTypes()->has($pubTypeName);
    }

    /**
     * Remove trailing slashes from the start and end of a string.
     *
     * @param  string  $string
     * @return string
     */
    public static function unslash(string $string): string
    {
        return trim($string, '/\\');
    }
}
