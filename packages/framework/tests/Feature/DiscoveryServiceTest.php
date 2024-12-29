<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Testing\UnitTestCase;

/**
 * Contains integration tests for the overall auto-discovery functionality.
 */
class DiscoveryServiceTest extends UnitTestCase
{
    protected array $filesToDelete = [];

    protected function setUp(): void
    {
        self::setupKernel();
        self::mockConfig();
    }

    protected function tearDown(): void
    {
        foreach ($this->filesToDelete as $file) {
            if (is_dir($file)) {
                Filesystem::deleteDirectory($file);
            } else {
                @unlink($file);
            }
        }
        $this->filesToDelete = [];
    }

    protected function file(string $path): void
    {
        $this->filesToDelete[] = Hyde::path($path);
        touch(Hyde::path($path));
    }

    protected function directory(string $path, bool $recursive = false): void
    {
        $this->filesToDelete[] = Hyde::path($path);
        @mkdir(Hyde::path($path), recursive: $recursive);
    }

    public function testGetSourceFileListForModelMethodFindsDefaultModelProperties()
    {
        $this->directory('foo');
        $this->unitTestMarkdownBasedPageList(MarkdownPage::class, '_pages'.'/foo.md');
    }

    public function testGetSourceFileListForModelMethodFindsCustomizedSourceDirectory()
    {
        $this->directory('foo');

        MarkdownPage::setSourceDirectory('foo');
        $this->unitTestMarkdownBasedPageList(MarkdownPage::class, 'foo/foo.md');

        MarkdownPage::setSourceDirectory('_pages');
    }

    public function testGetSourceFileListForModelMethodFindsCustomizedFileExtension()
    {
        $this->directory('foo');

        MarkdownPage::setSourceDirectory('foo');
        MarkdownPage::setFileExtension('.foo');

        $this->unitTestMarkdownBasedPageList(MarkdownPage::class, 'foo/foo.foo', 'foo');

        MarkdownPage::setSourceDirectory('_pages');
        MarkdownPage::setFileExtension('.md');
    }

    public function testGetMediaAssetFiles()
    {
        $this->assertTrue(is_array(MediaFile::files()));
    }

    public function testGetMediaAssetFilesDiscoversFiles()
    {
        $testFiles = ['png', 'svg', 'jpg', 'jpeg', 'gif', 'ico', 'css', 'js'];

        foreach ($testFiles as $type) {
            $this->file("_media/test.$type");
        }

        foreach ($testFiles as $type) {
            $this->assertContains("test.$type", MediaFile::files());
        }
    }

    public function testGetMediaAssetFilesDiscoversCustomFileTypes()
    {
        $path = 'test.custom';
        $this->file("_media/$path");
        $this->assertNotContains($path, MediaFile::files());
        self::mockConfig(['hyde.media_extensions' => ['custom']]);

        $this->resetKernel(); // Reboot to rediscover new files
        $this->assertContains($path, MediaFile::files());
    }

    public function testGetMediaAssetFilesDiscoversFilesRecursively()
    {
        $path = 'foo/bar.png';
        $this->directory('_media/foo');
        $this->file("_media/$path");
        $this->assertContains($path, MediaFile::files());
    }

    public function testGetMediaAssetFilesDiscoversFilesVeryRecursively()
    {
        $path = 'foo/bar/img.png';
        $this->directory(dirname("_media/$path"), recursive: true);
        $this->file("_media/$path");
        $this->assertContains($path, MediaFile::files());
        Filesystem::deleteDirectory('_media/foo');
    }

    public function testMediaAssetExtensionsCanBeAddedByCommaSeparatedValues()
    {
        self::mockConfig(['hyde.media_extensions' => []]);
        $this->file('_media/test.1');
        $this->file('_media/test.2');
        $this->file('_media/test.3');

        $this->assertSame([], MediaFile::files());

        self::mockConfig(['hyde.media_extensions' => ['1,2,3']]);
        $this->resetKernel(); // Reboot to rediscover new files
        $this->assertSame(['test.1', 'test.2', 'test.3'], MediaFile::files());
    }

    public function testMediaAssetExtensionsCanBeAddedByArray()
    {
        self::mockConfig(['hyde.media_extensions' => []]);
        $this->file('_media/test.1');
        $this->file('_media/test.2');
        $this->file('_media/test.3');

        $this->assertSame([], MediaFile::files());
        self::mockConfig(['hyde.media_extensions' => ['1', '2', '3']]);
        $this->resetKernel(); // Reboot to rediscover new files
        $this->assertSame(['test.1', 'test.2', 'test.3'], MediaFile::files());
    }

    public function testBladePageFilesStartingWithUnderscoreAreIgnored()
    {
        $this->file('_pages/_foo.blade.php');
        $this->assertSame(['404', 'index'], BladePage::files());
    }

    public function testMarkdownPageFilesStartingWithUnderscoreAreIgnored()
    {
        $this->file('_pages/_foo.md');
        $this->assertSame([], MarkdownPage::files());
    }

    public function testPostFilesStartingWithUnderscoreAreIgnored()
    {
        $this->file('_posts/_foo.md');
        $this->assertSame([], MarkdownPost::files());
    }

    public function testDocumentationPageFilesStartingWithUnderscoreAreIgnored()
    {
        $this->file('_docs/_foo.md');
        $this->assertSame([], DocumentationPage::files());
    }

    public function testBladePagePathToIdentifierHelperFormatsPathToIdentifier()
    {
        $this->assertSame('foo', BladePage::pathToIdentifier('foo'));
        $this->assertSame('foo', BladePage::pathToIdentifier('foo.blade.php'));
        $this->assertSame('foo/bar', BladePage::pathToIdentifier('foo/bar.blade.php'));

        $this->assertSame('foo', BladePage::pathToIdentifier(Hyde::path('_pages/foo.blade.php')));
        $this->assertSame('foo', BladePage::pathToIdentifier('_pages/foo.blade.php'));
    }

    public function testMarkdownPagePathToIdentifierHelperFormatsPathToIdentifier()
    {
        $this->assertSame('foo', MarkdownPage::pathToIdentifier('foo'));
        $this->assertSame('foo', MarkdownPage::pathToIdentifier('foo.md'));
        $this->assertSame('foo/bar', MarkdownPage::pathToIdentifier('foo/bar.md'));
    }

    public function testMarkdownPostPathToIdentifierHelperFormatsPathToIdentifier()
    {
        $this->assertSame('foo', MarkdownPost::pathToIdentifier('foo'));
        $this->assertSame('foo', MarkdownPost::pathToIdentifier('foo.md'));
        $this->assertSame('foo/bar', MarkdownPost::pathToIdentifier('foo/bar.md'));
    }

    public function testDocumentationPagePathToIdentifierHelperFormatsPathToIdentifier()
    {
        $this->assertSame('foo', DocumentationPage::pathToIdentifier('foo'));
        $this->assertSame('foo', DocumentationPage::pathToIdentifier('foo.md'));
        $this->assertSame('foo/bar', DocumentationPage::pathToIdentifier('foo/bar.md'));
    }

    protected function unitTestMarkdownBasedPageList(string $model, string $path, ?string $expected = null)
    {
        $this->file($path);
        Hyde::boot(); // Reboot to rediscover new pages

        $expected = $expected ?? basename($path, '.md');

        /** @var \Hyde\Pages\Concerns\HydePage $model */
        $this->assertSame([$expected], $model::files());
    }
}
