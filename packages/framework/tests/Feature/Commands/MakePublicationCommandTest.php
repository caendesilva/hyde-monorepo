<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Commands;

use function deleteDirectory;
use function file_get_contents;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * @covers \Hyde\Console\Commands\MakePublicationCommand
 * @covers \Hyde\Framework\Actions\CreatesNewPublicationFile
 */
class MakePublicationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        mkdir(Hyde::path('test-publication'));

        Carbon::setTestNow(Carbon::create(2022));
    }

    protected function tearDown(): void
    {
        deleteDirectory(Hyde::path('test-publication'));
        parent::tearDown();
    }

    public function test_command_creates_publication()
    {
        $this->makeSchemaFile();

        $this->artisan('make:publication')
            ->expectsOutputToContain('Creating a new Publication!')
            ->expectsChoice('Which publication type would you like to create a publication item for?', 0, ['test-publication'])
            ->expectsOutput("Creating a new publication of type [test-publication]")
            ->expectsQuestion('Title', 'Hello World')
            ->expectsOutput('Saving publication data to [test-publication/hello-world.md]')
            ->expectsOutput('Publication created successfully!')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(Hyde::path('test-publication/hello-world.md')));
        $this->assertEqualsIgnoringLineEndingType('---
__createdAt: 2022-01-01 00:00:00
title: Hello World
---
Raw MD text ...
', file_get_contents(Hyde::path('test-publication/hello-world.md')));
    }

    public function test_command_with_no_publication_types()
    {
        $this->artisan('make:publication')
            ->expectsOutputToContain('Creating a new Publication!')
            ->expectsOutput('Error: Unable to locate any publication types. Did you create any?')
            ->assertExitCode(1);
    }

    public function test_command_with_existing_publication()
    {
        $this->makeSchemaFile();
        touch(Hyde::path('test-publication/hello-world.md'));

        $this->artisan('make:publication')
            ->expectsOutputToContain('Creating a new Publication!')
            ->expectsChoice('Which publication type would you like to create a publication item for?', 0, ['test-publication'])
            ->expectsQuestion('Title', 'Hello World')
            // ->expectsOutput('Error: A publication with the title [Hello World] already exists.')
            ->expectsOutput('Error: File already exists: ' .Hyde::path('test-publication/hello-world.md'))
            ->expectsQuestion('Do you wish to overwrite the existing file (y/n)','n')
            ->assertExitCode(0);
    }

    protected function makeSchemaFile(): void
    {
        file_put_contents(
            Hyde::path('test-publication/schema.json'),
            json_encode([
                'name'           => 'Test Publication',
                'canonicalField' => 'title',
                'sortField'      => '__createdAt',
                'sortDirection'  => 'ASC',
                'pageSize'       => 10,
                'prevNextLinks'  => true,
                'detailTemplate' => 'test-publication_detail',
                'listTemplate'   => 'test-publication_list',
                'fields'         => [
                    [
                        'name' => 'title',
                        'min'  => '0',
                        'max'  => '0',
                        'type' => 'string',
                    ],
                ],
            ])
        );
    }
}
