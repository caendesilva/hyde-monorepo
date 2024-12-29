<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Foundation;

use Hyde\Framework\Actions\Internal\FileFinder;
use Mockery;
use Hyde\Foundation\Kernel\Filesystem;
use Hyde\Hyde;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem as BaseFilesystem;
use Mockery\MockInterface;

/**
 * @covers \Hyde\Foundation\Kernel\Filesystem
 */
class FilesystemHasMediaFilesTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    protected TestableFilesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new TestableFilesystem(Hyde::getInstance());

        $mock = Mockery::mock(BaseFilesystem::class)->makePartial();
        $mock->shouldReceive('missing')->andReturn(false)->byDefault();
        $mock->shouldReceive('size')->andReturn(100)->byDefault();
        $mock->shouldReceive('hash')->andReturn('hash')->byDefault();

        app()->instance(BaseFilesystem::class, $mock);
    }

    protected function tearDown(): void
    {
        $this->verifyMockeryExpectations();

        app()->forgetInstance(BaseFilesystem::class);
        app()->forgetInstance(FileFinder::class);

        Hyde::setMediaDirectory('_media');
    }

    public function testAssetsMethodReturnsSameInstanceOnSubsequentCalls()
    {
        $firstCall = $this->filesystem->assets();
        $secondCall = $this->filesystem->assets();

        $this->assertSame($firstCall, $secondCall);
    }

    public function testAssetsMethodReturnsEmptyCollectionWhenNoMediaFiles()
    {
        $this->filesystem->setTestMediaFiles([]);

        $assets = $this->filesystem->assets();

        $this->assertInstanceOf(Collection::class, $assets);
        $this->assertTrue($assets->isEmpty());
    }

    public function testAssetsMethodWithNestedDirectories()
    {
        $this->filesystem->setTestMediaFiles([
            Hyde::path('_media/images/photo.jpg'),
            Hyde::path('_media/documents/report.pdf'),
        ]);

        $assets = $this->filesystem->assets();

        $this->assertCount(2, $assets);
        $this->assertTrue($assets->has('images/photo.jpg'));
        $this->assertTrue($assets->has('documents/report.pdf'));
    }

    public function testUsesRecursiveFinderSearch()
    {
        $mock = $this->mockFileFinder();

        (new Filesystem(Hyde::getInstance()))->assets();

        $mock->shouldHaveReceived('handle')->with('_media', MediaFile::EXTENSIONS, true);
    }

    public function testItSupportsCustomMediaDirectory()
    {
        Hyde::setMediaDirectory('assets');

        $mock = $this->mockFileFinder();

        (new Filesystem(Hyde::getInstance()))->assets();

        $mock->shouldHaveReceived('handle')->with('assets', MediaFile::EXTENSIONS, true);
    }

    public function testItSupportsCustomExtensions()
    {
        self::mockConfig(['hyde.media_extensions' => ['gif', 'svg']]);

        $mock = $this->mockFileFinder();

        (new Filesystem(Hyde::getInstance()))->assets();

        $mock->shouldHaveReceived('handle')->with('_media', ['gif', 'svg'], true);
    }

    public function testDiscoverMediaFilesWithEmptyResult()
    {
        $this->filesystem->setTestMediaFiles([]);

        $result = $this->filesystem->getTestDiscoverMediaFiles();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testDiscoverMediaFilesWithMultipleFiles()
    {
        $this->filesystem->setTestMediaFiles([
            Hyde::path('_media/image.jpg'),
            Hyde::path('_media/document.pdf'),
        ]);

        $result = $this->filesystem->getTestDiscoverMediaFiles();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(MediaFile::class, $result->get('image.jpg'));
        $this->assertInstanceOf(MediaFile::class, $result->get('document.pdf'));
    }

    protected function mockFileFinder(): MockInterface
    {
        $mock = Mockery::mock(FileFinder::class);
        $mock->shouldReceive('handle')->andReturn(collect());
        app()->instance(FileFinder::class, $mock);

        return $mock;
    }
}

class TestableFilesystem extends Filesystem
{
    private static array $testMediaFiles = [];

    public function setTestMediaFiles(array $files): void
    {
        self::$testMediaFiles = $files;
    }

    protected static function getMediaFiles(): array
    {
        return self::$testMediaFiles;
    }

    public function callGetMediaFiles(): array
    {
        return parent::getMediaFiles();
    }

    public function getTestDiscoverMediaFiles(): Collection
    {
        return static::discoverMediaFiles();
    }
}
