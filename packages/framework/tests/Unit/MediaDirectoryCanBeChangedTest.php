<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Facades\Filesystem;
use Hyde\Foundation\Facades\Pages;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * High level test of the feature that allows the media source (_media) directory,
 * and the media output directory (_site/media) to be changed.
 *
 * @see \Hyde\Framework\Testing\Unit\BuildOutputDirectoryCanBeChangedTest
 * @see \Hyde\Framework\Testing\Feature\ConfigurableSourceRootsFeatureTest
 */
class MediaDirectoryCanBeChangedTest extends TestCase
{
    public function testMediaOutputDirectoryCanBeChangedForSiteBuilds()
    {
        Filesystem::deleteDirectory('_site');

        $this->directory('_assets');
        $this->file('_assets/app.css');

        Hyde::setMediaDirectory('_assets');

        $this->artisan('build');

        $this->assertDirectoryDoesNotExist(Hyde::path('_site/media'));
        $this->assertDirectoryExists(Hyde::path('_site/assets'));
        $this->assertFileExists(Hyde::path('_site/assets/app.css'));

        $this->resetSite();
    }

    public function testMediaOutputDirectoryCanBeChangedForSiteRebuilds()
    {
        Filesystem::deleteDirectory('_site');

        $this->directory('_assets');
        $this->file('_assets/app.css');

        Hyde::setMediaDirectory('_assets');

        $this->artisan('rebuild _assets');

        $this->assertDirectoryDoesNotExist(Hyde::path('_site/media'));
        $this->assertDirectoryExists(Hyde::path('_site/assets'));
        $this->assertFileExists(Hyde::path('_site/assets/app.css'));

        $this->resetSite();
    }

    public function testCompiledPagesHaveLinksToTheRightMediaFileLocation()
    {
        Filesystem::moveDirectory('_media', '_assets');
        Hyde::setMediaDirectory('_assets');
        $this->file('_assets/app.js');

        $this->file('_pages/foo.md');
        StaticPageBuilder::handle(Pages::getPage('_pages/foo.md'));

        $this->assertFileExists(Hyde::path('_site/foo.html'));
        $contents = file_get_contents(Hyde::path('_site/foo.html'));
        $this->assertStringContainsString(
            '<link rel="stylesheet" href="assets/app.css?v='.hash_file('crc32', Hyde::path('_assets/app.css')).'">',
            $contents
        );

        $this->assertStringContainsString(
            '<script defer src="assets/app.js?v='.hash_file('crc32', Hyde::path('_assets/app.js')).'"></script>',
            $contents
        );

        Filesystem::moveDirectory('_assets', '_media');
        Filesystem::delete('_site/foo.html');
        Filesystem::delete('_media/app.js');
    }
}
