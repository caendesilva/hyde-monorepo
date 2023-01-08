<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use function array_filter;
use function array_merge;
use function dirname;
use Exception;
use function file_get_contents;
use function file_put_contents;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Framework\Features\Publications\Paginator;
use Hyde\Framework\Features\Publications\PublicationService;
use Hyde\Hyde;
use Hyde\Pages\PublicationPage;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function json_decode;
use function json_encode;
use RuntimeException;
use function str_starts_with;

/**
 * @see \Hyde\Framework\Testing\Feature\PublicationTypeTest
 */
class PublicationType implements SerializableContract
{
    use Serializable;
    use InteractsWithDirectories;

    /** The "pretty" name of the publication type */
    public string $name;

    /**
     * The field name that is used as the canonical (or identifying) field of publications.
     *
     * It's used primarily for generating filenames, and the publications must thus be unique by this field.
     */
    public string $canonicalField = '__createdAt';

    /** The Blade filename or view identifier used for rendering a single publication */
    public string $detailTemplate = 'detail.blade.php';

    /** The Blade filename or view identifier used for rendering the index page (or index pages, when using pagination) */
    public string $listTemplate = 'list.blade.php';

    /** The pagination settings. Set to null to disable pagination. Make sure your list view supports it when enabled. */
    public null|PaginationSettings $pagination;

    /**
     * The front matter fields used for the publications.
     *
     * @var \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition>
     */
    public Collection $fields;

    /** The directory of the publication files */
    protected string $directory;

    public static function get(string $name): static
    {
        return static::fromFile("$name/schema.json");
    }

    public static function fromFile(string $schemaFile): static
    {
        try {
            return new static(...array_merge(
                static::parseSchemaFile($schemaFile),
                static::getRelativeDirectoryEntry($schemaFile))
            );
        } catch (Exception $exception) {
            throw new RuntimeException("Could not parse schema file $schemaFile", 0, $exception);
        }
    }

    public function __construct(
        string $name,
        string $canonicalField = '__createdAt',
        string $detailTemplate = 'detail.blade.php',
        string $listTemplate = 'list.blade.php',
        ?array $pagination = [],
        array $fields = [],
        ?string $directory = null
    ) {
        $this->name = $name;
        $this->canonicalField = $canonicalField;
        $this->detailTemplate = $detailTemplate;
        $this->listTemplate = $listTemplate;
        $this->fields = $this->parseFieldData($fields);
        $this->directory = $directory ?? Str::slug($name);
        $this->pagination = $this->evaluatePaginationSettings($pagination);
    }

    public function toArray(): array
    {
        return $this->withoutNullValues([
            'name' => $this->name,
            'canonicalField' => $this->canonicalField,
            'detailTemplate' => $this->detailTemplate,
            'listTemplate' => $this->listTemplate,
            'pagination' => $this->pagination?->toArray(),
            'fields' => $this->fields,
        ]);
    }

    public function toJson($options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $options);
    }

    /** Get the publication type's identifier */
    public function getIdentifier(): string
    {
        return $this->directory ?? Str::slug($this->name);
    }

    public function getSchemaFile(): string
    {
        return "$this->directory/schema.json";
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Get the raw field definitions for this publication type.
     *
     * @deprecated Use getFields() instead
     */
    public function getFieldData(): array
    {
        return $this->fields;
    }

    /**
     * Get the publication fields, deserialized to PublicationFieldDefinition objects.
     *
     * @see self::getFieldData() to get the raw field definitions.
     *
     * @return \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getFieldDefinition(string $fieldName): PublicationFieldDefinition
    {
        return $this->getFields()->filter(fn (PublicationFieldDefinition $field): bool => $field->name === $fieldName)->firstOrFail();
    }

    public function getCanonicalFieldDefinition(): PublicationFieldDefinition
    {
        if (str_starts_with($this->canonicalField, '__')) {
            return new PublicationFieldDefinition('string', $this->canonicalField);
        }

        return $this->getFields()->filter(fn (PublicationFieldDefinition $field): bool => $field->name === $this->canonicalField)->first();
    }

    /** @return \Illuminate\Support\Collection<\Hyde\Pages\PublicationPage> */
    public function getPublications(): Collection
    {
        return PublicationService::getPublicationsForPubType($this);
    }

    public function getPaginator(int $currentPageNumber = null): Paginator
    {
        return new Paginator($this->getPublicationsSortedByPaginationField(),
            $this->pagination->pageSize,
            $currentPageNumber,
            $this->getIdentifier()
        );
    }

    public function getListPage(): PublicationListPage
    {
        return new PublicationListPage($this);
    }

    public function usesPagination(): bool
    {
        return ($this->pagination->pageSize > 0) && ($this->pagination->pageSize < $this->getPublications()->count());
    }

    public function save(?string $path = null): void
    {
        $path ??= $this->getSchemaFile();
        $this->needsParentDirectory($path);
        file_put_contents(Hyde::path($path), json_encode($this->toArray(), JSON_PRETTY_PRINT));
    }

    protected static function parseSchemaFile(string $schemaFile): array
    {
        return json_decode(file_get_contents(Hyde::path($schemaFile)), true, 512, JSON_THROW_ON_ERROR);
    }

    protected static function getRelativeDirectoryEntry(string $schemaFile): array
    {
        return ['directory' => Hyde::pathToRelative(dirname($schemaFile))];
    }

    protected function getPublicationsSortedByPaginationField(): Collection
    {
        return $this->getPublications()->sortBy(function (PublicationPage $page): mixed {
            return $page->matter($this->pagination->sortField);
        }, descending: ! $this->pagination->sortAscending)->values();
    }

    protected function parseFieldData(array $fields): Collection
    {
        return Collection::make($fields)->mapWithKeys(function (array $data): array {
            return [$data['name'] => new PublicationFieldDefinition(...$data)];
        });
    }

    protected function evaluatePaginationSettings(array $pagination): ?PaginationSettings
    {
        if (empty($pagination)) {
            return null;
        }

        return PaginationSettings::fromArray($pagination);
    }

    protected function withoutNullValues(array $array): array
    {
        return array_filter($array, fn (mixed $value): bool => ! is_null($value));
    }
}
