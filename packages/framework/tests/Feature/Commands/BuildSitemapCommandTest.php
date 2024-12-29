<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Commands;

use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Console\Commands\BuildSitemapCommand
 * @covers \Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap
 */
class BuildSitemapCommandTest extends TestCase
{
    public function testSitemapIsGeneratedWhenConditionsAreMet()
    {
        config(['hyde.url' => 'https://example.com']);

        $this->cleanUpWhenDone('_site/sitemap.xml');

        $this->assertFileDoesNotExist(Hyde::path('_site/sitemap.xml'));

        $this->artisan('build:sitemap')
            ->expectsOutputToContain('Generating sitemap...')
            ->doesntExpectOutputToContain('Skipped')
            ->expectsOutputToContain(' > Created _site/sitemap.xml')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/sitemap.xml'));
    }

    public function testSitemapIsNotGeneratedWhenConditionsAreNotMet()
    {
        config(['hyde.url' => '']);

        $this->assertFileDoesNotExist(Hyde::path('_site/sitemap.xml'));

        $this->artisan('build:sitemap')
            ->expectsOutputToContain('Generating sitemap...')
            ->expectsOutputToContain('Skipped')
            ->expectsOutput(' > Cannot generate sitemap without a valid base URL')
            ->assertExitCode(3);

        $this->assertFileDoesNotExist(Hyde::path('_site/sitemap.xml'));
    }
}
