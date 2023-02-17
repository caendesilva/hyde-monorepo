<?php

declare(strict_types=1);

namespace Hyde\Publications;

use Hyde\Pages\Concerns\HydePage;
use function glob;
use Hyde\Hyde;
use Hyde\Publications\Models\PublicationPage;
use Hyde\Publications\Models\PublicationTags;
use Hyde\Publications\Models\PublicationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @see \Hyde\Publications\Testing\Feature\PublicationServiceTest
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
        return Collection::make(static::getSchemaFiles())->mapWithKeys(function (string $schemaFile): array {
            $publicationType = PublicationType::fromFile(Hyde::pathToRelative($schemaFile));

            return [$publicationType->getDirectory() => $publicationType];
        });
    }

    /**
     * Return all publications for a given publication type.
     */
    public static function getPublicationsForPubType(PublicationType $pubType): Collection
    {
        return collect(Hyde::pages()->where(function (HydePage $page) use ($pubType): bool {
            return $page instanceof PublicationPage && $page::$publicationType === $pubType->getDirectory();
        })->sortBy(function (PublicationPage $page) use ($pubType): mixed {
            return $page->matter($pubType->sortField);
        }, descending: ! $pubType->sortAscending)->values());
    }

    /**
     * Return all media items for a given publication type.
     */
    public static function getMediaForPubType(PublicationType $pubType): Collection
    {
        return Collection::make(static::getMediaFiles($pubType->getDirectory()))->map(function (string $file): string {
            return Hyde::pathToRelative($file);
        });
    }

    /**
     * Get all available tags.
     */
    public static function getAllTags(): Collection
    {
        return PublicationTags::getAllTags();
    }

    /**
     * Get all values for a given tag name.
     */
    public static function getValuesForTagName(string $tagName): Collection
    {
        return collect(PublicationTags::getValuesForTagName($tagName));
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

    protected static function getMediaFiles(string $directory, string $extensions = '{jpg,jpeg,png,gif,pdf}'): array
    {
        return glob(Hyde::mediaPath("$directory/*.$extensions"), GLOB_BRACE);
    }
}
