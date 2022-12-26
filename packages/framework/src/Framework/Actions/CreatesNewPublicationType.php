<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Framework\Actions\Concerns\CreateAction;
use Hyde\Framework\Actions\Contracts\CreateActionContract;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Deprecated;

/**
 * Scaffold a new publication type schema.
 *
 * @see \Hyde\Console\Commands\MakePublicationCommand
 * @see \Hyde\Framework\Testing\Feature\Actions\CreatesNewPublicationTypeTest
 */
class CreatesNewPublicationType extends CreateAction implements CreateActionContract
{
    protected string $dirName;

    public function __construct(
        protected string $name,
        protected Collection $fields,
        protected string $canonicalField,
        protected ?string $sortField,
        protected ?bool $sortAscending,
        protected ?bool $prevNextLinks,
        protected ?int $pageSize,
    ) {
        $this->dirName = $this->formatStringForStorage($this->name);
        $this->outputPath = "$this->dirName/schema.json";
    }

    protected function handleCreate(): void
    {
        $type = new PublicationType(
            $this->name,
            $this->canonicalField,
            "{$this->dirName}_detail",
            "{$this->dirName}_list",
            [
                $this->sortField ?? '__createdAt',
                $this->sortAscending ?? true,
                $this->prevNextLinks ?? true,
                $this->pageSize ?? 25,
            ],
            $this->fields->toArray()
        );

        $type->save($this->outputPath);

        // TODO: Generate the detail and list templates
    }
}
