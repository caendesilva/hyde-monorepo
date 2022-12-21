<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications;

use Hyde\Framework\Actions\SourceFileParser;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Hyde;
use Hyde\Pages\PublicationPage;
use Illuminate\Support\Str;
use Rgasch\Collection\Collection;
use function Safe\file_get_contents;
use function Safe\glob;
use function Safe\json_decode;

/**
 * @see \Hyde\Framework\Testing\Feature\Services\PublicationServiceTest
 */
class PublicationService
{
    /**
     * Return a collection of all defined publication types, indexed by the directory name.
     *
     * @todo We might want to refactor to cache this in the Kernel, maybe under $publications?
     *
     * @return Collection<string, PublicationType>
     */
    public static function getPublicationTypes(): Collection
    {
        return Collection::create(static::getSchemaFiles())->mapWithKeys(function (string $schemaFile): array {
            $publicationType = PublicationType::fromFile(Hyde::pathToRelative($schemaFile));

            return [$publicationType->getDirectory() => $publicationType];
        });
    }

    /**
     * Return all publications for a given publication type.
     */
    public static function getPublicationsForPubType(PublicationType $pubType): Collection
    {
        return Collection::create(static::getPublicationFiles($pubType->getDirectory()))->map(function (string $file): PublicationPage {
            return static::parsePublicationFile(Hyde::pathToRelative($file));
        });
    }

    /**
     * Return all media items for a given publication type.
     */
    public static function getMediaForPubType(PublicationType $pubType): Collection
    {
        return Collection::create(static::getMediaFiles($pubType->getDirectory()))->map(function (string $file): string {
            return Hyde::pathToRelative($file);
        });
    }

    /**
     * Get all available tags.
     *
     * @return \Rgasch\Collection\Collection
     *
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    public static function getAllTags(): Collection
    {
        $filename = Hyde::path('tags.json');
        if (! file_exists($filename)) {
            return Collection::create();
        }

        return Collection::create(json_decode(file_get_contents($filename), true))->sortKeys();
    }

    /**
     * Get all values for a given tag name.
     *
     * @param  string  $tagName
     * @return \Rgasch\Collection\Collection
     *
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Safe\Exceptions\JsonException
     */
    public static function getValuesForTagName(string $tagName): Collection
    {
        $tags = static::getAllTags();
        if (! $tags->get($tagName)) {
            return Collection::create();
        }

        return $tags->$tagName;
    }

    /**
     * Parse a publication Markdown source file and return a PublicationPage object.
     *
     * @param  string  $identifier  Example: my-publication/hello.md or my-publication/hello
     */
    public static function parsePublicationFile(string $identifier): PublicationPage
    {
        return (new SourceFileParser(PublicationPage::class, Str::replaceLast('.md', '', $identifier)))->get();
    }

    /**
     * Check whether a given publication type exists.
     */
    public static function publicationTypeExists(string $pubTypeName): bool
    {
        return static::getPublicationTypes()->has(Str::slug($pubTypeName));
    }

    protected static function getSchemaFiles(): array
    {
        return glob(Hyde::path(Hyde::getSourceRoot()).'/*/schema.json');
    }

    protected static function getPublicationFiles(string $directory): array
    {
        return glob(Hyde::path("$directory/*.md"));
    }

    protected static function getMediaFiles(string $directory, string $extensions = '{jpg,jpeg,png,gif,pdf}'): array
    {
        return glob(Hyde::path("_media/$directory/*.$extensions"), GLOB_BRACE);
    }
}
