<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Foundation;

use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\Kernel\Hyperlinks;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Foundation\Kernel\Hyperlinks
 */
class HyperlinksTest extends TestCase
{
    protected Hyperlinks $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new Hyperlinks(HydeKernel::getInstance());
    }

    public function testAssetHelperGetsRelativeWebLinkToImageStoredInSiteMediaFolder()
    {
        $this->file('_media/test.jpg');

        $this->assertSame('media/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperResolvesPathsForNestedPages()
    {
        $this->file('_media/test.jpg');

        $this->mockCurrentPage('foo/bar');
        $this->assertSame('../media/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsQualifiedAbsoluteUriWhenSiteHasBaseUrl()
    {
        config(['hyde.url' => 'https://example.org']);
        $this->file('_media/test.jpg');
        $this->assertSame('https://example.org/media/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsDefaultRelativePathWhenSiteHasNoBaseUrl()
    {
        $this->withoutSiteUrl();
        $this->file('_media/test.jpg');
        $this->assertSame('media/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsDefaultRelativePathWhenSiteBaseUrlIsLocalhost()
    {
        $this->file('_media/test.jpg');
        $this->assertSame('media/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperUsesConfiguredMediaDirectory()
    {
        Hyde::setMediaDirectory('_assets');
        $this->file('_assets/test.jpg');
        $this->assertSame('assets/test.jpg?v=00000000', (string) $this->class->asset('test.jpg'));
    }

    public function testAssetHelperThrowsExceptionForNonExistentFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->class->asset('non_existent_file.jpg');
    }

    public function testAssetHelperCanGetFileWithNoExtension()
    {
        $this->file('_media/no_extension');
        $this->assertInstanceOf(MediaFile::class, $this->class->asset('no_extension'));
    }

    public function testAssetHelperCanGetFileWithNonMediaExtension()
    {
        $this->file('_media/test.foo');
        $this->assertInstanceOf(MediaFile::class, $this->class->asset('test.foo'));
    }

    public function testAssetHelperThrowsExceptionWithHelpfulMessage()
    {
        $this->expectExceptionMessage('File [_media/test.png] not found when trying to resolve a media asset.');
        $this->expectException(FileNotFoundException::class);
        $this->class->asset('test.png');
    }

    public function testAssetHelperReturnsInputWhenImageIsAlreadyQualifiedRegardlessOfMatchingTheConfiguredUrl()
    {
        $this->expectExceptionMessage('File [_media/http://localhost/media/test.jpg] not found when trying to resolve a media asset.');
        $this->expectException(FileNotFoundException::class);

        config(['hyde.url' => 'https://example.org']);
        $this->assertSame('http://localhost/media/test.jpg?v=00000000', (string) $this->class->asset('http://localhost/media/test.jpg'));
    }

    public function testAssetHelper()
    {
        $this->file('_media/foo', 'test');
        $class = $this->class;
        $this->assertSame('media/foo?v=accf8b33', (string) $class->asset('foo'));
    }

    public function testAssetHelperWithRelativePath()
    {
        $this->mockCurrentPage('foo/bar');
        $this->file('_media/foo', 'test');
        $class = $this->class;
        $this->assertSame('../media/foo?v=accf8b33', (string) $class->asset('foo'));
    }

    public function testAssetHelperWithExistingFile()
    {
        $this->file('_media/foo', 'test');
        $class = $this->class;
        $this->assertSame('media/foo?v=accf8b33', (string) $class->asset('foo'));
    }

    public function testAssetHelperWithNonExistingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $class = $this->class;
        (string) $class->asset('foo');
    }

    public function testRouteHelper()
    {
        $this->assertNotNull($this->class->route('index'));
        $this->assertSame(Routes::get('index'), $this->class->route('index'));
    }

    public function testRouteHelperWithInvalidRoute()
    {
        $this->assertNull($this->class->route('foo'));
    }

    public function testIsRemoteWithHttpUrl()
    {
        $this->assertTrue(Hyperlinks::isRemote('http://example.com'));
    }

    public function testIsRemoteWithHttpsUrl()
    {
        $this->assertTrue(Hyperlinks::isRemote('https://example.com'));
    }

    public function testIsRemoteWithProtocolRelativeUrl()
    {
        $this->assertTrue(Hyperlinks::isRemote('//example.com'));
    }

    public function testIsRemoteWithRelativeUrl()
    {
        $this->assertFalse(Hyperlinks::isRemote('/path/to/resource'));
    }

    public function testIsRemoteWithAbsoluteLocalPath()
    {
        $this->assertFalse(Hyperlinks::isRemote('/var/www/html/index.php'));
    }

    public function testIsRemoteWithEmptyString()
    {
        $this->assertFalse(Hyperlinks::isRemote(''));
    }
}
