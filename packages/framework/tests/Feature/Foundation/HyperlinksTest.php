<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Foundation;

use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\Kernel\Hyperlinks;
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

        $this->assertSame('media/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperResolvesPathsForNestedPages()
    {
        $this->file('_media/test.jpg');

        $this->mockCurrentPage('foo/bar');
        $this->assertSame('../media/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsQualifiedAbsoluteUriWhenSiteHasBaseUrl()
    {
        config(['hyde.url' => 'https://example.org']);
        $this->file('_media/test.jpg');
        $this->assertSame('https://example.org/media/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsDefaultRelativePathWhenSiteHasNoBaseUrl()
    {
        $this->withoutSiteUrl();
        $this->file('_media/test.jpg');
        $this->assertSame('media/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperReturnsDefaultRelativePathWhenSiteBaseUrlIsLocalhost()
    {
        $this->file('_media/test.jpg');
        $this->assertSame('media/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperUsesConfiguredMediaDirectory()
    {
        Hyde::setMediaDirectory('_assets');
        $this->file('_assets/test.jpg');
        $this->assertSame('assets/test.jpg', $this->class->asset('test.jpg'));
    }

    public function testAssetHelperThrowsExceptionForNonExistentFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->class->asset('non_existent_file.jpg');
    }

    public function testAssetHelperThrowsExceptionFileWithNoExtension()
    {
        $this->file('_media/no_extension');
        $this->expectException(FileNotFoundException::class);
        $this->class->asset('no_extension');
    }

    public function testAssetHelperThrowsExceptionForNonExistentFileNonMediaExtension()
    {
        $this->file('_media/test.foo');
        $this->expectException(FileNotFoundException::class);
        $this->class->asset('test.foo');
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
        $this->assertSame('http://localhost/media/test.jpg', $this->class->asset('http://localhost/media/test.jpg'));
    }

    public function testMediaLinkHelper()
    {
        $this->assertSame('media/foo', $this->class->mediaLink('foo'));
    }

    public function testMediaLinkHelperWithRelativePath()
    {
        $this->mockCurrentPage('foo/bar');
        $this->assertSame('../media/foo', $this->class->mediaLink('foo'));
    }

    public function testMediaLinkHelperUsesConfiguredMediaDirectory()
    {
        Hyde::setMediaDirectory('_assets');
        $this->assertSame('assets/foo', $this->class->mediaLink('foo'));
    }

    public function testMediaLinkHelperWithValidationAndExistingFile()
    {
        $this->file('_media/foo', 'test');
        $this->assertSame('media/foo?v=accf8b33', $this->class->mediaLink('foo', true));
    }

    public function testMediaLinkHelperWithValidationAndNonExistingFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->class->mediaLink('foo', true);
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
