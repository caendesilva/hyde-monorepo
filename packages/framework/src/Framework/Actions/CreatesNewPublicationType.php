<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Framework\Actions\Concerns\CreateAction;
use Hyde\Hyde;
use Hyde\Publications\Models\PublicationType;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Scaffold a new publication type schema.
 *
 * @see \Hyde\Console\Commands\MakePublicationCommand
 * @see \Hyde\Framework\Testing\Feature\Actions\CreatesNewPublicationTypeTest
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
        $this->savePublicationFile('detail.blade.php', '/../publications/resources/views/publication_detail.blade.php');
    }

    protected function createListTemplate(): void
    {
        $this->savePublicationFile('list.blade.php', '/../publications/resources/views/publication_list.blade.php');
    }

    protected function savePublicationFile(string $filename, string $viewPath): void
    {
        copy(Hyde::vendorPath($viewPath), Hyde::path("$this->directoryName/$filename"));
    }
}
