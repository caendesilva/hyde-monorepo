<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Support\BuildWarnings;
use Hyde\Testing\TestCase;
use Hyde\Foundation\Facades\Pages;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Hyde\Framework\HydeServiceProvider;
use Hyde\Framework\Actions\StaticPageBuilder;

/**
 * @covers \Hyde\Console\Commands\BuildSiteCommand
 * @covers \Hyde\Framework\Services\BuildService
 * @covers \Hyde\Framework\Actions\PreBuildTasks\CleanSiteDirectory
 * @covers \Hyde\Framework\Actions\PreBuildTasks\TransferMediaAssets
 */
class StaticSiteServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSite();

        Process::preventStrayProcesses();
    }

    protected function tearDown(): void
    {
        File::cleanDirectory(Hyde::path('_site'));

        parent::tearDown();
    }

    public function testBuildCommandContainsExpectedOutput()
    {
        $this->artisan('build')
            ->expectsOutputToContain('Building your static site')
            ->expectsOutputToContain('All done! Finished in')
            ->expectsOutput('Congratulations! 🎉 Your static site has been built!')
            ->assertExitCode(0);
    }

    public function testBuildCommandCreatesHtmlFiles()
    {
        $this->file('_posts/test-post.md');

        $this->artisan('build')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/index.html'));
        $this->assertFileExists(Hyde::path('_site/posts/test-post.html'));
    }

    public function testBuildCommandTransfersMediaAssetFiles()
    {
        $this->file('_media/test-image.png', 'foo');
        $this->artisan('build')
            ->expectsOutputToContain('Transferring Media Assets...');

        $this->assertFileEquals(Hyde::path('_media/test-image.png'), Hyde::path('_site/media/test-image.png'));

        Filesystem::unlink('_site/media/test-image.png');
    }

    public function testBuildCommandTransfersMediaAssetFilesRecursively()
    {
        $this->directory('_media/foo');

        $this->file('_media/foo/img.png', 'foo');
        $this->artisan('build')->assertSuccessful();
        $this->assertFileEquals(Hyde::path('_media/foo/img.png'), Hyde::path('_site/media/foo/img.png'));
    }

    public function testBuildCommandCreatesNeededMediaDirectoriesRecursively()
    {
        $this->directory('_media/foo/bar');
        $this->file('_media/1.png');
        $this->file('_media/foo/2.png');
        $this->file('_media/foo/bar/3.png');

        Filesystem::deleteDirectory('_site');

        $this->artisan('build')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/media/1.png'));
        $this->assertFileExists(Hyde::path('_site/media/foo/2.png'));
        $this->assertFileExists(Hyde::path('_site/media/foo/bar/3.png'));
    }

    public function testBuildCommandSkipsMediaTransferWhenThereAreNoAssets()
    {
        rename(Hyde::path('_media/app.css'), Hyde::path('_media/app.css.bak'));

        $this->artisan('build')
            ->expectsOutputToContain('Transferring Media Assets... ')
            ->expectsOutputToContain('Skipped')
            ->expectsOutputToContain('> No media files to transfer.')
            ->assertExitCode(0);

        rename(Hyde::path('_media/app.css.bak'), Hyde::path('_media/app.css'));
    }

    public function testAllPageTypesCanBeCompiled()
    {
        $this->file('_pages/html.html');
        $this->file('_pages/blade.blade.php');
        $this->file('_pages/markdown.md');
        $this->file('_posts/post.md');
        $this->file('_docs/docs.md');

        $this->artisan('build')
            ->expectsOutput('Creating Html Pages...')
            ->expectsOutput('Creating Blade Pages...')
            ->expectsOutput('Creating Markdown Pages...')
            ->expectsOutput('Creating Markdown Posts...')
            ->expectsOutput('Creating Documentation Pages...')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/html.html'));
        $this->assertFileExists(Hyde::path('_site/blade.html'));
        $this->assertFileExists(Hyde::path('_site/markdown.html'));
        $this->assertFileExists(Hyde::path('_site/posts/post.html'));
        $this->assertFileExists(Hyde::path('_site/docs/docs.html'));
    }

    public function testOnlyProgressBarsForTypesWithPagesAreShown()
    {
        $this->file('_pages/blade.blade.php');
        $this->file('_pages/markdown.md');

        $this->artisan('build')
            ->doesntExpectOutput('Creating Html Pages...')
            ->expectsOutput('Creating Blade Pages...')
            ->expectsOutput('Creating Markdown Pages...')
            ->doesntExpectOutput('Creating Markdown Posts...')
            ->doesntExpectOutput('Creating Documentation Pages...')
            ->doesntExpectOutputToContain('Creating')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/blade.html'));
        $this->assertFileExists(Hyde::path('_site/markdown.html'));
    }

    public function testPrintInitialInformationAllowsApiToBeDisabled()
    {
        $this->artisan('build --no-api')
            ->expectsOutput('Disabling external API calls')
            ->assertExitCode(0);
    }

    public function testNodeActionOutputs()
    {
        Process::fake();

        $this->artisan('build --run-prettier --run-vite')
            ->expectsOutput('Building frontend assets for production! This may take a second.')
            ->expectsOutput('Prettifying code! This may take a second.')
            ->assertExitCode(0);

        Process::assertRan(fn ($process) => $process->command === 'npm run build');
        Process::assertRan(fn ($process) => $process->command === 'npx prettier '.Hyde::pathToRelative(Hyde::sitePath()).'/**/*.html --write --bracket-same-line');
    }

    public function testPrettyUrlsOptionOutput()
    {
        $this->artisan('build --pretty-urls')
            ->expectsOutput('Generating site with pretty URLs')
            ->assertExitCode(0);
    }

    public function testSitemapIsNotGeneratedWhenConditionsAreNotMet()
    {
        $this->withoutSiteUrl();
        config(['hyde.generate_sitemap' => false]);

        $this->artisan('build')
            ->doesntExpectOutput('Generating sitemap...')
            ->assertExitCode(0);
    }

    public function testSitemapIsGeneratedWhenConditionsAreMet()
    {
        $this->withSiteUrl();
        config(['hyde.generate_sitemap' => true]);

        $this->artisan('build')
            // ->expectsOutput('Generating sitemap...')
            ->assertExitCode(0);
        Filesystem::unlink('_site/sitemap.xml');
    }

    public function testRssFeedIsNotGeneratedWhenConditionsAreNotMet()
    {
        $this->withoutSiteUrl();
        config(['hyde.rss.enabled' => false]);

        $this->artisan('build')
            ->doesntExpectOutput('Generating RSS feed...')
            ->assertExitCode(0);
    }

    public function testRssFeedIsGeneratedWhenConditionsAreMet()
    {
        $this->withSiteUrl();
        config(['hyde.rss.enabled' => true]);

        Filesystem::touch('_posts/foo.md');

        $this->artisan('build')
            // ->expectsOutput('Generating RSS feed...')
            ->assertExitCode(0);

        Filesystem::unlink('_posts/foo.md');
        Filesystem::unlink('_site/feed.xml');
    }

    public function testDoesNotGenerateSearchFilesWhenConditionsAreNotMet()
    {
        $this->artisan('build')
            ->doesntExpectOutput('Generating search index...')
            ->doesntExpectOutput('Generating search page...')
            ->assertExitCode(0);
    }

    public function testGeneratesSearchFilesWhenConditionsAreMet()
    {
        Filesystem::touch('_docs/foo.md');

        $this->artisan('build')
            // ->expectsOutput('Generating search index...')
            // ->expectsOutput('Generating search page...')
            ->assertExitCode(0);

        Filesystem::unlink('_docs/foo.md');
    }

    public function testSiteDirectoryIsEmptiedBeforeBuild()
    {
        Filesystem::touch('_site/foo.html');
        $this->artisan('build')
            ->expectsOutputToContain('Removing all files from build directory...')
            ->assertExitCode(0);
        $this->assertFileDoesNotExist(Hyde::path('_site/foo.html'));
    }

    public function testOutputDirectoryIsNotEmptiedIfDisabledInConfig()
    {
        config(['hyde.empty_output_directory' => false]);
        Filesystem::touch('_site/keep.html');

        $this->artisan('build')
            ->doesntExpectOutput('Removing all files from build directory...')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('_site/keep.html'));
        Filesystem::unlink('_site/keep.html');
    }

    public function testAbortsWhenNonStandardDirectoryIsEmptied()
    {
        Hyde::setOutputDirectory('foo');

        mkdir(Hyde::path('foo'));
        Filesystem::touch('foo/keep.html');

        $this->artisan('build')
            ->expectsOutputToContain('Removing all files from build directory...')
            ->expectsQuestion('The configured output directory (foo) is potentially unsafe to empty. Are you sure you want to continue?', false)
            ->expectsOutput('Output directory will not be emptied.')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('foo/keep.html'));
        File::deleteDirectory(Hyde::path('foo'));
    }

    public function testWithoutWarnings()
    {
        $this->artisan('build')
            ->doesntExpectOutput('There were some warnings during the build process:')
            ->assertExitCode(0);
    }

    public function testWithWarnings()
    {
        BuildWarnings::report('This is a warning');

        $this->artisan('build')
            ->expectsOutput('There were some warnings during the build process:')
            ->expectsOutput(' 1. This is a warning')
            ->assertExitCode(0);
    }

    public function testWithWarningsAndVerbose()
    {
        BuildWarnings::report('This is a warning');

        $this->artisan('build --verbose')
            ->expectsOutput('There were some warnings during the build process:')
            ->expectsOutput(' 1. This is a warning')
            ->expectsOutputToContain('BuildWarnings.php')
            ->assertExitCode(0);
    }

    public function testWithWarningsButWarningsAreDisabled()
    {
        config(['hyde.log_warnings' => false]);
        BuildWarnings::report('This is a warning');

        $this->artisan('build')
            ->doesntExpectOutput('There were some warnings during the build process:')
            ->assertExitCode(0);
    }

    public function testWithWarningsConvertedToExceptions()
    {
        config(['hyde.convert_build_warnings_to_exceptions' => true]);
        BuildWarnings::report('This is a warning');

        $this->artisan('build')
            ->expectsOutput('There were some warnings during the build process:')
            ->expectsOutputToContain('Hyde\Framework\Exceptions\BuildWarning')
            ->doesntExpectOutput(' 1. This is a warning')
            ->assertExitCode(2);
    }

    public function testSiteOutputDirectoryCanBeChangedForSiteBuilds()
    {
        $this->file('_posts/test-post.md');

        Hyde::setOutputDirectory('_site/build');

        $this->withoutMockingConsoleOutput();
        $this->artisan('build');

        $this->assertFileExists(Hyde::path('_site/build/posts/test-post.html'));
        $this->assertFileExists(Hyde::path('_site/build/media/app.css'));
        $this->assertFileExists(Hyde::path('_site/build/index.html'));

        File::deleteDirectory(Hyde::path('_site/build'));
    }

    public function testSiteOutputDirectoryCanBeChangedInStaticPageBuilder()
    {
        $this->file('_posts/test-post.md');

        Hyde::setOutputDirectory('_site/build');

        StaticPageBuilder::handle(Pages::getPage('_posts/test-post.md'));

        $this->assertFileExists(Hyde::path('_site/build/posts/test-post.html'));

        File::deleteDirectory(Hyde::path('_site/build'));
    }

    public function testOutputDirectoryIsCreatedIfItDoesNotExistInStaticPageBuilder()
    {
        $this->file('_posts/test-post.md');

        File::deleteDirectory(Hyde::path('_site/build/foo'));
        Hyde::setOutputDirectory('_site/build/foo');

        StaticPageBuilder::handle(Pages::getPage('_posts/test-post.md'));

        $this->assertFileExists(Hyde::path('_site/build/foo/posts/test-post.html'));

        File::deleteDirectory(Hyde::path('_site/build/foo'));
    }

    public function testSiteOutputDirectoryCanBeChangedInConfiguration()
    {
        $this->assertSame('_site', Hyde::kernel()->getOutputDirectory());

        config(['hyde.output_directory' => '_site/build']);
        (new HydeServiceProvider($this->app))->register();

        $this->assertSame('_site/build', Hyde::kernel()->getOutputDirectory());

        $this->file('_posts/test-post.md');

        StaticPageBuilder::handle(Pages::getPage('_posts/test-post.md'));

        $this->assertFileExists(Hyde::path('_site/build/posts/test-post.html'));

        File::deleteDirectory(Hyde::path('_site/build'));
    }
}
