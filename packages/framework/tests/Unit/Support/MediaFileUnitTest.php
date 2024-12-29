<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Support;

use Mockery;
use Illuminate\View\Factory;
use Hyde\Foundation\HydeKernel;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\RenderData;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Hyde;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Testing\UnitTestCase;
use Illuminate\Filesystem\Filesystem as BaseFilesystem;

/**
 * @covers \Hyde\Support\Filesystem\MediaFile
 *
 * @see \Hyde\Framework\Testing\Feature\Support\MediaFileTest
 */
class MediaFileUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    protected static string $originalBasePath;

    protected $mockFilesystem;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$originalBasePath = Hyde::getBasePath();

        Hyde::setBasePath('/base/path');
    }

    public static function tearDownAfterClass(): void
    {
        Hyde::setBasePath(static::$originalBasePath);

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        $this->mockFilesystem = Mockery::mock(BaseFilesystem::class);
        app()->instance(BaseFilesystem::class, $this->mockFilesystem);

        // Set up default expectations for commonly called methods
        $this->mockFilesystem->shouldReceive('isFile')->andReturn(true)->byDefault();
        $this->mockFilesystem->shouldReceive('missing')->andReturn(false)->byDefault();
        $this->mockFilesystem->shouldReceive('exists')->andReturn(true)->byDefault();
        $this->mockFilesystem->shouldReceive('extension')->andReturn('txt')->byDefault();
        $this->mockFilesystem->shouldReceive('size')->andReturn(12)->byDefault();
        $this->mockFilesystem->shouldReceive('mimeType')->andReturn('text/plain')->byDefault();
        $this->mockFilesystem->shouldReceive('hash')->andReturn(hash('crc32', 'Hello World!'))->byDefault();
        $this->mockFilesystem->shouldReceive('get')->andReturn('Hello World!')->byDefault();

        // Mock render data
        \Illuminate\Support\Facades\View::swap(Mockery::mock(Factory::class)->makePartial());
        Render::swap(new RenderData());

        self::mockConfig(['hyde.cache_busting' => false]);
    }

    protected function tearDown(): void
    {
        $this->verifyMockeryExpectations();
    }

    public function testCanConstruct()
    {
        $file = new MediaFile('foo');

        $this->assertInstanceOf(MediaFile::class, $file);
        $this->assertSame('_media/foo', $file->path);
    }

    public function testCanMake()
    {
        $this->assertEquals(new MediaFile('foo'), MediaFile::make('foo'));
    }

    public function testCanGet()
    {
        $kernel = HydeKernel::getInstance();

        $hyde = Mockery::mock(Hyde::kernel())->makePartial();
        $hyde->shouldReceive('assets')->andReturn(collect(['app.css' => new MediaFile('_media/app.css')]));

        HydeKernel::setInstance($hyde);

        $this->assertEquals(new MediaFile('app.css'), MediaFile::get('app.css'));

        HydeKernel::setInstance($kernel);
    }

    public function testCanConstructWithNestedPaths()
    {
        $this->assertSame('_media/path/to/file.txt', MediaFile::make('path/to/file.txt')->path);
    }

    public function testPathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make('foo')->path);
    }

    public function testAbsolutePathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make(Hyde::path('foo'))->path);
    }

    public function testMediaPathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make('_media/foo')->path);
    }

    public function testAbsoluteMediaPathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make(Hyde::path('_media/foo'))->path);
    }

    public function testOutputMediaPathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make('media/foo')->path);
    }

    public function testAbsoluteOutputMediaPathIsNormalizedToRelativeMediaPath()
    {
        $this->assertSame('_media/foo', MediaFile::make(Hyde::path('media/foo'))->path);
    }

    public function testCustomMediaPathsAreNormalizedToRelativeCustomizedMediaPath()
    {
        Hyde::setMediaDirectory('bar');

        $this->assertSame('bar/foo', MediaFile::make('foo')->path);
        $this->assertSame('bar/foo', MediaFile::make('bar/foo')->path);
        $this->assertSame('bar/foo', MediaFile::make(Hyde::path('foo'))->path);

        Hyde::setMediaDirectory('_bar');

        $this->assertSame('_bar/foo', MediaFile::make('foo')->path);
        $this->assertSame('_bar/foo', MediaFile::make('_bar/foo')->path);
        $this->assertSame('_bar/foo', MediaFile::make(Hyde::path('_bar/foo'))->path);
        $this->assertSame('_bar/foo', MediaFile::make('bar/foo')->path);
        $this->assertSame('_bar/foo', MediaFile::make(Hyde::path('foo'))->path);

        Hyde::setMediaDirectory('_media');
    }

    public function testConstructorWithVariousInputFormats()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make('foo.txt')->path);
        $this->assertSame('_media/foo.txt', MediaFile::make('_media/foo.txt')->path);
        $this->assertSame('_media/foo.txt', MediaFile::make(Hyde::path('_media/foo.txt'))->path);
        $this->assertSame('_media/foo.txt', MediaFile::make('media/foo.txt')->path);
    }

    public function testConstructorSetsProperties()
    {
        $file = new MediaFile('foo.txt');
        $this->assertNotNull($file->getLength());
        $this->assertNotNull($file->getMimeType());
        $this->assertNotNull($file->getHash());
    }

    public function testNormalizePathWithAbsolutePath()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make(Hyde::path('_media/foo.txt'))->path);
    }

    public function testNormalizePathWithRelativePath()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make('foo.txt')->path);
    }

    public function testNormalizePathWithOutputDirectoryPath()
    {
        Hyde::setMediaDirectory('_custom_media');
        $this->assertSame('_custom_media/foo.txt', MediaFile::make('custom_media/foo.txt')->path);
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testNormalizePathWithAlreadyCorrectFormat()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make('_media/foo.txt')->path);
    }

    public function testNormalizePathWithParentDirectoryReferences()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make('../_media/foo.txt')->path);
        $this->assertSame('_media/baz/../bar/foo.txt', MediaFile::make('_media/baz/../bar/foo.txt')->path); // We don't do anything about this
    }

    public function testGetNameReturnsNameOfFile()
    {
        $this->assertSame('foo.txt', MediaFile::make('foo.txt')->getName());
        $this->assertSame('bar.txt', MediaFile::make('foo/bar.txt')->getName());
    }

    public function testGetPathReturnsPathOfFile()
    {
        $this->assertSame('_media/foo.txt', MediaFile::make('foo.txt')->getPath());
        $this->assertSame('_media/foo/bar.txt', MediaFile::make('foo/bar.txt')->getPath());
    }

    public function testGetAbsolutePathReturnsAbsolutePathOfFile()
    {
        $this->assertSame(Hyde::path('_media/foo.txt'), MediaFile::make('foo.txt')->getAbsolutePath());
        $this->assertSame(Hyde::path('_media/foo/bar.txt'), MediaFile::make('foo/bar.txt')->getAbsolutePath());
    }

    public function testGetContentsReturnsContentsOfFile()
    {
        $this->mockFilesystem->shouldReceive('get')
            ->andReturn('foo bar')
            ->once();

        $this->assertSame('foo bar', MediaFile::make('foo.txt')->getContents());
    }

    public function testGetExtensionReturnsExtensionOfFile()
    {
        $this->mockFilesystem->shouldReceive('extension')
            ->with(Hyde::path('_media/foo.txt'))
            ->andReturn('txt');

        $this->mockFilesystem->shouldReceive('extension')
            ->with(Hyde::path('_media/foo.png'))
            ->andReturn('png');

        $this->assertSame('txt', MediaFile::make('foo.txt')->getExtension());
        $this->assertSame('png', MediaFile::make('foo.png')->getExtension());
    }

    public function testToArrayReturnsArrayOfFileProperties()
    {
        $this->mockFilesystem->shouldReceive('size')
            ->with(Hyde::path('_media/foo.txt'))
            ->andReturn(7);

        $this->mockFilesystem->shouldReceive('mimeType')
            ->with(Hyde::path('_media/foo.txt'))
            ->andReturn('text/plain');

        $this->mockFilesystem->shouldReceive('hash')
            ->with(Hyde::path('_media/foo.txt'), 'crc32')
            ->andReturn(hash('crc32', 'foo bar'));

        $this->assertSame([
            'name' => 'foo.txt',
            'path' => '_media/foo.txt',
            'length' => 7,
            'mimeType' => 'text/plain',
            'hash' => hash('crc32', 'foo bar'),
        ], MediaFile::make('foo.txt')->toArray());
    }

    public function testGetLength()
    {
        $this->mockFilesystem->shouldReceive('size')
            ->with(Hyde::path('_media/foo'))
            ->andReturn(12);

        $this->assertSame(12, MediaFile::make('foo')->getLength());
    }

    public function testGetMimeType()
    {
        $this->mockFilesystem->shouldReceive('mimeType')
            ->with(Hyde::path('_media/foo.txt'))
            ->andReturn('text/plain');

        $this->assertSame('text/plain', MediaFile::make('foo.txt')->getMimeType());
    }

    public function testGetHashReturnsHash()
    {
        $this->mockFilesystem->shouldReceive('hash')
            ->with(Hyde::path('_media/foo.txt'), 'crc32')
            ->andReturn(hash('crc32', 'Hello World!'));

        $this->assertSame(hash('crc32', 'Hello World!'), MediaFile::make('foo.txt')->getHash());
    }

    public function testGetMimeTypeReturnsLookupValueForKnownExtension()
    {
        $this->mockFilesystem->shouldReceive('extension')
            ->with(Hyde::path('_media/foo.txt'))
            ->andReturn('txt');

        $this->assertSame('text/plain', MediaFile::make('foo.txt')->getMimeType());
    }

    public function testGetMimeTypeUsesFileinfoForUnknownExtension()
    {
        $this->mockFilesystem->shouldReceive('extension')
            ->with(Hyde::path('_media/foo.xyz'))
            ->andReturn('xyz');

        $this->mockFilesystem->shouldReceive('exists')
            ->with(Hyde::path('_media/foo.xyz'))
            ->andReturn(true);

        $this->mockFilesystem->shouldReceive('mimeType')
            ->with(Hyde::path('_media/foo.xyz'))
            ->andReturn('application/octet-stream');

        $this->assertSame('application/octet-stream', MediaFile::make('foo.xyz')->getMimeType());
    }

    public function testGetMimeTypeReturnsTextPlainForNonExistentFile()
    {
        $this->mockFilesystem->shouldReceive('extension')
            ->with(Hyde::path('_media/nonexistent.xyz'))
            ->andReturn('xyz');

        $this->mockFilesystem->shouldReceive('exists')
            ->with(Hyde::path('_media/nonexistent.xyz'))
            ->andReturn(false);

        $this->assertSame('text/plain', MediaFile::make('nonexistent.xyz')->getMimeType());
    }

    /** @dataProvider bootableMethodsProvider */
    public function testExceptionIsThrownWhenBootingFileThatDoesNotExist(string $bootableMethod)
    {
        $this->mockFilesystem->shouldReceive('missing')
            ->with(Hyde::path('_media/foo'))
            ->andReturn(true);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [_media/foo] not found when trying to resolve a media asset.');

        MediaFile::make('foo')->$bootableMethod();
    }

    public function testExceptionIsNotThrownWhenConstructingFileThatDoesExist()
    {
        $this->mockFilesystem->shouldReceive('missing')
            ->with(Hyde::path('_media/foo'))
            ->andReturn(false);

        $this->assertInstanceOf(MediaFile::class, MediaFile::make('foo'));
    }

    public function testGetIdentifierWithSubdirectory()
    {
        $this->assertSame('foo/bar', MediaFile::make('foo/bar')->getIdentifier());
    }

    public function testGetIdentifierReturnsIdentifierWithFileExtension()
    {
        $this->assertSame('foo.png', MediaFile::make('foo.png')->getIdentifier());
    }

    public function testGetIdentifierWithSubdirectoryWithFileExtension()
    {
        $this->assertSame('foo/bar.png', MediaFile::make('foo/bar.png')->getIdentifier());
    }

    public function testHelperForMediaPath()
    {
        $this->assertSame('/base/path/_media', MediaFile::sourcePath());
    }

    public function testHelperForMediaPathReturnsPathToFileWithinTheDirectory()
    {
        $this->assertSame('/base/path/_media/foo.css', MediaFile::sourcePath('foo.css'));
    }

    public function testGetMediaPathReturnsAbsolutePath()
    {
        $this->assertSame('/base/path/_media', MediaFile::sourcePath());
    }

    public function testHelperForMediaOutputPath()
    {
        $this->assertSame('/base/path/_site/media', MediaFile::outputPath());
    }

    public function testHelperForMediaOutputPathReturnsPathToFileWithinTheDirectory()
    {
        $this->assertSame('/base/path/_site/media/foo.css', MediaFile::outputPath('foo.css'));
    }

    public function testGetMediaOutputPathReturnsAbsolutePath()
    {
        $this->assertSame('/base/path/_site/media', MediaFile::outputPath());
    }

    public function testCanGetSiteMediaOutputDirectory()
    {
        $this->assertSame('/base/path/_site/media', MediaFile::outputPath());
    }

    public function testGetSiteMediaOutputDirectoryUsesTrimmedVersionOfMediaSourceDirectory()
    {
        Hyde::setMediaDirectory('_foo');
        $this->assertSame('/base/path/_site/foo', MediaFile::outputPath());
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testGetSiteMediaOutputDirectoryUsesConfiguredSiteOutputDirectory()
    {
        Hyde::setOutputDirectory('/base/path/foo');
        Hyde::setMediaDirectory('bar');

        $this->assertSame('/base/path/foo/bar', MediaFile::outputPath());

        Hyde::setOutputDirectory('/base/path/_site'); // Reset to default
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testSourcePathWithEmptyString()
    {
        $this->assertSame(Hyde::path('_media'), MediaFile::sourcePath(''));
    }

    public function testSourcePathWithSubdirectories()
    {
        $this->assertSame(Hyde::path('_media/foo/bar'), MediaFile::sourcePath('foo/bar'));
    }

    public function testSourcePathWithLeadingSlash()
    {
        $this->assertSame(Hyde::path('_media/foo'), MediaFile::sourcePath('/foo'));
    }

    public function testOutputPathWithEmptyString()
    {
        $this->assertSame(Hyde::sitePath('media'), MediaFile::outputPath(''));
    }

    public function testOutputPathWithSubdirectories()
    {
        $this->assertSame(Hyde::sitePath('media/foo/bar'), MediaFile::outputPath('foo/bar'));
    }

    public function testOutputPathWithLeadingSlash()
    {
        $this->assertSame(Hyde::sitePath('media/foo'), MediaFile::outputPath('/foo'));
    }

    public function testGetLink()
    {
        $file = MediaFile::make('foo.txt');
        $this->assertSame('media/foo.txt', $file->getLink());
    }

    public function testGetLinkWithCustomMediaDirectory()
    {
        Hyde::setMediaDirectory('custom_media');
        $file = MediaFile::make('foo.txt');
        $this->assertSame('custom_media/foo.txt', $file->getLink());
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testGetLinkWithPrettyUrls()
    {
        self::mockConfig(['hyde.cache_busting' => false, 'hyde.pretty_urls' => true]);
        $file = MediaFile::make('foo.txt');
        $this->assertSame('media/foo.txt', $file->getLink());
    }

    public function testGetLinkWithNestedFile()
    {
        $file = MediaFile::make('subdirectory/foo.txt');
        $this->assertSame('media/subdirectory/foo.txt', $file->getLink());
    }

    public function testGetLinkWithBaseUrl()
    {
        self::mockConfig(['hyde.cache_busting' => false, 'hyde.url' => 'https://example.com']);
        $file = MediaFile::make('foo.txt');
        $this->assertSame('https://example.com/media/foo.txt', $file->getLink());
    }

    public function testGetLinkWithBaseUrlAndPrettyUrls()
    {
        self::mockConfig([
            'hyde.cache_busting' => false,
            'hyde.url' => 'https://example.com',
            'hyde.pretty_urls' => true,
        ]);
        $file = MediaFile::make('foo.txt');
        $this->assertSame('https://example.com/media/foo.txt', $file->getLink());
    }

    public function testGetLinkWithCacheBusting()
    {
        self::mockConfig(['hyde.cache_busting' => true]);
        $this->mockFilesystem->shouldReceive('hash')
            ->andReturn('abc123');
        $file = MediaFile::make('foo.txt');
        $this->assertSame('media/foo.txt?v=abc123', $file->getLink());
    }

    public function testGetLinkWithCacheBustingDisabled()
    {
        self::mockConfig(['hyde.cache_busting' => false]);
        $file = MediaFile::make('foo.txt');
        $this->assertSame('media/foo.txt', $file->getLink());
    }

    public function testGetLinkWithCurrentPageContext()
    {
        $this->mockCurrentPage('foo/bar');
        $file = MediaFile::make('baz.txt');
        $this->assertSame('../media/baz.txt', $file->getLink());
    }

    public function testGetLinkWithCurrentPageContextAndCustomMediaDirectory()
    {
        Hyde::setMediaDirectory('custom_media');
        $this->mockCurrentPage('foo/bar');
        $file = MediaFile::make('baz.txt');
        $this->assertSame('../custom_media/baz.txt', $file->getLink());
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testCanCastToString()
    {
        $file = new MediaFile('foo.txt');
        $this->assertIsString((string) $file);
        $this->assertSame('media/foo.txt', (string) $file);
    }

    public function testStringCastReturnsLink()
    {
        $file = new MediaFile('foo.txt');
        $this->assertSame($file->getLink(), (string) $file);
    }

    public function testStringCastWithCustomMediaDirectory()
    {
        Hyde::setMediaDirectory('custom_media');
        $file = new MediaFile('foo.txt');
        $this->assertSame('custom_media/foo.txt', (string) $file);
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testStringCastWithPrettyUrls()
    {
        self::mockConfig(['hyde.cache_busting' => false, 'hyde.pretty_urls' => true]);
        $file = new MediaFile('foo.txt');
        $this->assertSame('media/foo.txt', (string) $file);
    }

    public function testStringCastWithNestedFile()
    {
        $file = new MediaFile('subdirectory/foo.txt');
        $this->assertSame('media/subdirectory/foo.txt', (string) $file);
    }

    public function testStringCastWithBaseUrl()
    {
        self::mockConfig(['hyde.cache_busting' => false, 'hyde.url' => 'https://example.com']);
        $file = new MediaFile('foo.txt');
        $this->assertSame('https://example.com/media/foo.txt', (string) $file);
    }

    public function testStringCastWithCacheBusting()
    {
        self::mockConfig(['hyde.cache_busting' => true]);
        $this->mockFilesystem->shouldReceive('hash')
            ->andReturn('abc123');
        $file = new MediaFile('foo.txt');
        $this->assertSame('media/foo.txt?v=abc123', (string) $file);
    }

    public function testStringCastWithCurrentPageContext()
    {
        $this->mockCurrentPage('foo/bar');
        $file = new MediaFile('baz.txt');
        $this->assertSame('../media/baz.txt', (string) $file);
    }

    public function testGetOutputPath()
    {
        $file = MediaFile::make('foo.txt');
        $this->assertSame('/base/path/_site/media/foo.txt', $file->getOutputPath());
    }

    public function testGetOutputPathWithNestedFile()
    {
        $file = MediaFile::make('subdirectory/foo.txt');
        $this->assertSame('/base/path/_site/media/subdirectory/foo.txt', $file->getOutputPath());
    }

    public function testGetOutputPathWithCustomMediaDirectory()
    {
        Hyde::setMediaDirectory('custom_media');
        $file = MediaFile::make('foo.txt');
        $this->assertSame('/base/path/_site/custom_media/foo.txt', $file->getOutputPath());
        Hyde::setMediaDirectory('_media'); // Reset to default
    }

    public function testGetOutputPathWithCustomOutputDirectory()
    {
        Hyde::setOutputDirectory('/base/path/custom_output');
        $file = MediaFile::make('foo.txt');
        $this->assertSame('/base/path/custom_output/media/foo.txt', $file->getOutputPath());
        Hyde::setOutputDirectory('/base/path/_site'); // Reset to default
    }

    // Helper method to mock the current page
    protected function mockCurrentPage(string $page): void
    {
        Render::shouldReceive('getRouteKey')->andReturn($page);
    }

    public static function bootableMethodsProvider(): array
    {
        return [
            ['getLength'],
            ['getMimeType'],
            ['getHash'],
        ];
    }
}
