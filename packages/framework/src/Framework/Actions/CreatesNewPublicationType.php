<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use function file_get_contents;
use function file_put_contents;
use Hyde\Framework\Actions\Concerns\CreateAction;
use Hyde\Framework\Actions\Contracts\CreateActionContract;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Hyde;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Scaffold a new publication type schema.
 *
 * @see \Hyde\Console\Commands\MakePublicationCommand
 * @see \Hyde\Framework\Testing\Feature\Actions\CreatesNewPublicationTypeTest
 */
class CreatesNewPublicationType extends CreateAction implements CreateActionContract
{
    protected string $directoryName;

    public function __construct(
        protected string $name,
        protected Arrayable $fields,
        protected ?string $canonicalField = null,
        protected ?string $sortField = null,
        protected ?bool $sortAscending = null,
        protected ?bool $prevNextLinks = null,
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
            [
                $this->sortField ?? '__createdAt',
                $this->sortAscending ?? true,
                $this->prevNextLinks ?? true,
                $this->pageSize ?? 25,
            ],
            $this->fields->toArray()
        ))->save($this->outputPath);

        $this->createDetailTemplate();
        $this->createListTemplate();
    }

    protected function createDetailTemplate(): void
    {
        $contents = <<<'BLADE'
        @extends('hyde::layouts.app')
        @section('content')
            <main id="content" class="mx-auto max-w-7xl py-16 px-8">
                <article class="prose dark:prose-invert">
                    @php/** @var \Hyde\Pages\PublicationPage $publication*/@endphp
                    <h1>{{ $publication->title }}</h1>
                    <p>
                        {{ $publication->markdown }}
                    </p>
                </article>
                
                <div class="prose dark:prose-invert my-8">
                    <hr>
                </div>
                
                <article class="prose dark:prose-invert">
                    <h3>Front Matter Data</h3>
                    <div class="ml-4">
                        @foreach($publication->matter->data as $key => $value)
                        <dt class="font-bold">{{ $key }}</dt>
                        <dd class="ml-4">
                            {{ is_array($value) ? '(array) '. implode(', ', $value) : $value }}
                        </dd>
                        @endforeach
                    </div>
                </article>
            </main>
        @endsection
        BLADE;

        $this->savePublicationFile('detail.blade.php', $contents);
    }

    protected function createListTemplate(): void
    {
        $this->savePublicationFile('list.blade.php', file_get_contents(Hyde::vendorPath('resources/views/layouts/publication_list.blade.php')));
    }

    protected function savePublicationFile(string $filename, string $contents): int
    {
        return file_put_contents(Hyde::path("$this->directoryName/$filename"), "$contents\n");
    }
}
