<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Commands;

use function deleteDirectory;
use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Console\Commands\MakePublicationTypeCommand
 * @covers \Hyde\Framework\Actions\CreatesNewPublicationType
 */
class MakePublicationTypeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        deleteDirectory(Hyde::path('test-publication'));

        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    public function test_command_creates_publication_type()
    {
        $this->artisan('make:publicationType')
            ->expectsQuestion('Publication type name', 'Test Publication')
            ->expectsQuestion('Field name', 'Publication Title')
            ->expectsQuestion('Field type (1-10)', 1)
            ->expectsQuestion('Min value (for strings, this refers to string length)', 'default')
            ->expectsQuestion('Max value (for strings, this refers to string length)', 'default')
            ->expectsQuestion('<bg=magenta;fg=white>Add another field (y/n)</>', 'n')
            ->expectsChoice('Choose the default field you wish to sort by', 'dateCreated (meta field)', [
                'dateCreated (meta field)',
                'publication-title',
            ])
            ->expectsChoice('Choose the default sort direction', 'Ascending (oldest items first if sorting by dateCreated)', [
                'Ascending (oldest items first if sorting by dateCreated)',
                'Descending (newest items first if sorting by dateCreated)',
            ])
            ->expectsQuestion('Enter the pageSize (0 for no limit)', 10)
            ->expectsQuestion('Generate previous/next links in detail view (y/n)', 'n')
            ->expectsChoice('Choose a canonical name field (the values of this field have to be unique!)', 'publication-title', [
                'publication-title',
            ])
            ->expectsOutputToContain('Creating a new Publication Type!')
            ->expectsOutput('Saving publication data to [test-publication/schema.json]')
            ->expectsOutput('Publication type created successfully!')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('test-publication/schema.json'));
        $this->assertEqualsIgnoringLineEndingType(
            <<<'JSON'
            {
                "name": "Test Publication",
                "canonicalField": "publication-title",
                "sortField": "__createdAt",
                "sortDirection": "ASC",
                "pageSize": 10,
                "prevNextLinks": true,
                "detailTemplate": "test-publication_detail",
                "listTemplate": "test-publication_list",
                "fields": [
                    {
                        "name": "publication-title",
                        "min": "default",
                        "max": "default",
                        "type": "string"
                    }
                ]
            }
            JSON,
            file_get_contents(Hyde::path('test-publication/schema.json'))
        );

        // TODO: Assert Blade templates were created?
    }
}
