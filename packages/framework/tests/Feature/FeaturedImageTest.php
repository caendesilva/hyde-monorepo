<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use BadMethodCallException;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Factories\FeaturedImageFactory;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Framework\Features\Blogging\Models\LocalFeaturedImage;
use Hyde\Framework\Features\Blogging\Models\RemoteFeaturedImage;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * @covers \Hyde\Framework\Features\Blogging\Models\FeaturedImage
 * @covers \Hyde\Framework\Features\Blogging\Models\LocalFeaturedImage
 * @covers \Hyde\Framework\Features\Blogging\Models\RemoteFeaturedImage
 */
class FeaturedImageTest extends TestCase
{
    public function testGetAltText()
    {
        $this->assertNull((new NullImage)->getAltText());
        $this->assertEquals('alt', (new FilledImage)->getAltText());
    }

    public function testGetTitleText()
    {
        $this->assertNull((new NullImage)->getTitleText());
        $this->assertEquals('title', (new FilledImage)->getTitleText());
    }

    public function testGetAuthorName()
    {
        $this->assertNull((new NullImage)->getAuthorName());
        $this->assertEquals('author', (new FilledImage)->getAuthorName());
    }

    public function testGetAuthorUrl()
    {
        $this->assertNull((new NullImage)->getAuthorUrl());
        $this->assertEquals('authorUrl', (new FilledImage)->getAuthorUrl());
    }

    public function testGetCopyrightText()
    {
        $this->assertNull((new NullImage)->getCopyrightText());
        $this->assertEquals('copyright', (new FilledImage)->getCopyrightText());
    }

    public function testGetLicenseName()
    {
        $this->assertNull((new NullImage)->getLicenseName());
        $this->assertEquals('license', (new FilledImage)->getLicenseName());
    }

    public function testGetLicenseUrl()
    {
        $this->assertNull((new NullImage)->getLicenseUrl());
        $this->assertEquals('licenseUrl', (new FilledImage)->getLicenseUrl());
    }

    public function testHasAltText()
    {
        $this->assertFalse((new NullImage)->hasAltText());
        $this->assertTrue((new FilledImage)->hasAltText());
    }

    public function testHasTitleText()
    {
        $this->assertFalse((new NullImage)->hasTitleText());
        $this->assertTrue((new FilledImage)->hasTitleText());
    }

    public function testHasAuthorName()
    {
        $this->assertFalse((new NullImage)->hasAuthorName());
        $this->assertTrue((new FilledImage)->hasAuthorName());
    }

    public function testHasAuthorUrl()
    {
        $this->assertFalse((new NullImage)->hasAuthorUrl());
        $this->assertTrue((new FilledImage)->hasAuthorUrl());
    }

    public function testHasCopyrightText()
    {
        $this->assertFalse((new NullImage)->hasCopyrightText());
        $this->assertTrue((new FilledImage)->hasCopyrightText());
    }

    public function testHasLicenseName()
    {
        $this->assertFalse((new NullImage)->hasLicenseName());
        $this->assertTrue((new FilledImage)->hasLicenseName());
    }

    public function testHasLicenseUrl()
    {
        $this->assertFalse((new NullImage)->hasLicenseUrl());
        $this->assertTrue((new FilledImage)->hasLicenseUrl());
    }

    public function testGetType()
    {
        $this->assertEquals('local', (new LocalImage)->getType());
        $this->assertEquals('remote', (new RemoteImage)->getType());
    }

    public function testGetMetadataArray()
    {
        $this->assertSame([
            'url' => 'media/source',
            'contentUrl' => 'media/source',
        ], (new NullImage)->getMetadataArray());

        $this->assertSame([
            'text' => 'alt',
            'name' => 'title',
            'url' => 'media/source',
            'contentUrl' => 'media/source',
        ], (new FilledImage)->getMetadataArray());

        $this->assertSame([
            'url' => 'media/source',
            'contentUrl' => 'media/source',
        ], (new LocalImage)->getMetadataArray());

        $this->assertSame([
            'url' => 'https://example.com',
            'contentUrl' => 'https://example.com',
        ], (new RemoteImage)->getMetadataArray());
    }

    public function testGetContentLength()
    {
        $this->assertEquals(0, (new NullImage)->getContentLength());
        $this->assertEquals(0, (new FilledImage)->getContentLength());
    }

    public function testCanConstructLocalFeaturedImage()
    {
        $image = new LocalFeaturedImage('_media/foo', ...$this->defaultArguments());
        $this->assertInstanceOf(LocalFeaturedImage::class, $image);
        $this->assertInstanceOf(FeaturedImage::class, $image);

        $this->assertEquals('media/foo', $image->getSource());
    }

    public function testFeaturedImageGetContentLength()
    {
        $this->file('_media/foo', 'image');

        $image = new LocalFeaturedImage('_media/foo', ...$this->defaultArguments());
        $this->assertEquals(5, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithNoSource()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Image at _media/foo does not exist');

        $image = new LocalFeaturedImage('_media/foo', ...$this->defaultArguments());
        $this->assertEquals(0, $image->getContentLength());
    }

    public function testCanConstructRemoteFeaturedImage()
    {
        $image = new RemoteFeaturedImage('http/foo', ...$this->defaultArguments());
        $this->assertInstanceOf(RemoteFeaturedImage::class, $image);
        $this->assertInstanceOf(FeaturedImage::class, $image);

        $this->assertEquals('http/foo', $image->getSource());
    }

    public function testCanConstructRemoteFeaturedImageWithHttps()
    {
        $image = new RemoteFeaturedImage('https/foo', ...$this->defaultArguments());
        $this->assertInstanceOf(RemoteFeaturedImage::class, $image);
        $this->assertInstanceOf(FeaturedImage::class, $image);

        $this->assertEquals('https/foo', $image->getSource());
    }

    public function testCanConstructRemoteFeaturedImageWithInvalidSource()
    {
        $image = new RemoteFeaturedImage('foo', ...$this->defaultArguments());
        $this->assertEquals('foo', $image->getSource());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSource()
    {
        Http::fake(function () {
            return Http::response(null, 200, [
                'Content-Length' => 16,
            ]);
        });

        $image = new RemoteFeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertEquals(16, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSourceAndNotFoundResponse()
    {
        Http::fake(function () {
            return Http::response(null, 404);
        });

        $image = new RemoteFeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertEquals(0, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSourceAndInvalidResponse()
    {
        Http::fake(function () {
            return Http::response(null, 200, [
                'Content-Length' => 'foo',
            ]);
        });

        $image = new RemoteFeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertEquals(0, $image->getContentLength());
    }

    public function testGetSourceMethod()
    {
        $this->assertEquals('media/foo', (new LocalFeaturedImage('_media/foo', ...$this->defaultArguments()))->getSource());

        $this->assertEquals('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.path' => 'foo']))->getSource());
        $this->assertEquals('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.path' => 'media/foo']))->getSource());
        $this->assertEquals('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.path' => '_media/foo']))->getSource());

        $this->assertEquals('foo', FeaturedImageFactory::make(new FrontMatter(['image.url' => 'foo']))->getSource());
        $this->assertEquals('//foo', FeaturedImageFactory::make(new FrontMatter(['image.url' => '//foo']))->getSource());
        $this->assertEquals('http', FeaturedImageFactory::make(new FrontMatter(['image.url' => 'http']))->getSource());

        $this->assertEquals('media/foo', FeaturedImageFactory::make(new FrontMatter(['image' => 'foo']))->getSource());
        $this->assertEquals('http', FeaturedImageFactory::make(new FrontMatter(['image' => 'http']))->getSource());
    }

    public function testMagicCallResolverWithNonExistentGetProperty()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Method 'getFoo' does not exist on " . NullImage::class);

        /** @noinspection PhpUndefinedMethodInspection */
        (new NullImage())->getFoo();
    }

    public function testMagicCallResolverWithNonExistentHasProperty()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Method 'hasFoo' does not exist on " . NullImage::class);

        /** @noinspection PhpUndefinedMethodInspection */
        (new NullImage())->hasFoo();
    }

    public function testMagicCallResolverWithNonExistentMethodPrefix()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Method 'foo' does not exist on " . NullImage::class);

        /** @noinspection PhpUndefinedMethodInspection */
        (new NullImage())->foo();
    }

    protected function defaultArguments(): array
    {
        return ['alt', 'title', 'author', 'authorUrl', 'copyright', 'license', 'licenseUrl'];
    }
}

class LocalImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source');
    }

    public function getContentLength(): int
    {
        return 0;
    }
}

class RemoteImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('https://example.com');
    }

    public function getContentLength(): int
    {
        return 0;
    }
}

class NullImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source');
    }

    public function getContentLength(): int
    {
        return 0;
    }
}

class FilledImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source', 'alt', 'title', 'author', 'authorUrl', 'copyright', 'license', 'licenseUrl');
    }

    public function getContentLength(): int
    {
        return 0;
    }
}
