<?php

declare(strict_types=1);

namespace Hyde\Publications\Actions;

use Hyde\Framework\Actions\Concerns\CreateAction;
use Hyde\Hyde;
use Hyde\Publications\Models\PublicationType;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Scaffold a new publication type schema.
 *
 * @see \Hyde\Publications\Commands\MakePublicationCommand
 * @see \Hyde\Publications\Testing\Feature\CreatesNewPublicationTypeTest
 */
class CreatesNewPublicationType extends CreateAction
{
    protected string $directoryName;

    public function __construct(
        protected string $name,
        protected Arrayable $fields,
        protected ?string $canonicalField = null,
        protected ?string $sortField = null,
        protected ?bool $sortAscending = null,
        protected ?int $pageSize = null,
    ) {
        $this->directoryName = $this->formatStringForStorage($this->name);
        $this->outputPath = "$this->directoryName/schema.json";
    }

    protected function handleCreate(): void
    {
        (new PublicationType(
            $this->name,
            $this->canonicalField ?? '__createdAt',
            'detail.blade.php',
            'list.blade.php',
            $this->sortField ?? '__createdAt',
            $this->sortAscending ?? true,
            $this->pageSize ?? 25,
            $this->fields->toArray()
        ))->save($this->outputPath);

        $this->createDetailTemplate();
        $this->createListTemplate();
    }

    protected function createDetailTemplate(): void
    {
        $this->savePublicationFile('detail.blade.php', 'publication_detail');
    }

    protected function createListTemplate(): void
    {
        $this->savePublicationFile('list.blade.php', sprintf("%s",
            $this->usesPagination() ? 'publication_paginated_list' : 'publication_list'
        ));
    }

    protected function savePublicationFile(string $filename, string $viewPath): void
    {
        copy(Hyde::vendorPath("/../publications/resources/views/$viewPath.blade.php"), Hyde::path("$this->directoryName/$filename"));
    }

    protected function usesPagination(): bool
    {
        return $this->pageSize > 0;
    }
}
