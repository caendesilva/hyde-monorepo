<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Factories\FeaturedImageFactory;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * @covers \Hyde\Framework\Features\Blogging\Models\FeaturedImage
 *
 * @see \Hyde\Framework\Testing\Unit\FeaturedImageUnitTest
 */
class FeaturedImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hyde.cache_busting' => false]);

        $this->file('_media/foo', 'test');
        $this->file('_media/source');
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

    public function testCanConstructFeaturedImage()
    {
        $image = new FeaturedImage('_media/foo', ...$this->defaultArguments());
        $this->assertInstanceOf(FeaturedImage::class, $image);

        $this->assertSame('media/foo', $image->getSource());
    }

    public function testFeaturedImageGetContentLength()
    {
        $image = new FeaturedImage('_media/foo', ...$this->defaultArguments());
        $this->assertSame(4, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSource()
    {
        Http::fake(function () {
            return Http::response(null, 200, [
                'Content-Length' => 16,
            ]);
        });

        $image = new FeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertSame(16, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSourceAndNotFoundResponse()
    {
        Http::fake(function () {
            return Http::response(null, 404);
        });

        $image = new FeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertSame(0, $image->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithRemoteSourceAndInvalidResponse()
    {
        Http::fake(function () {
            return Http::response(null, 200, [
                'Content-Length' => 'foo',
            ]);
        });

        $image = new FeaturedImage('https://hyde.test/static/image.png', ...$this->defaultArguments());
        $this->assertSame(0, $image->getContentLength());
    }

    public function testGetSourceMethod()
    {
        $this->assertSame('media/foo', (new FeaturedImage('_media/foo', ...$this->defaultArguments()))->getSource());

        $this->assertSame('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'foo']))->getSource());
        $this->assertSame('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'media/foo']))->getSource());
        $this->assertSame('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.source' => '_media/foo']))->getSource());

        $this->assertSame('media/foo', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'foo']))->getSource());
        $this->assertSame('//foo', FeaturedImageFactory::make(new FrontMatter(['image.source' => '//foo']))->getSource());
        $this->assertSame('http', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'http']))->getSource());

        $this->assertSame('media/foo', FeaturedImageFactory::make(new FrontMatter(['image' => 'foo']))->getSource());
        $this->assertSame('http', FeaturedImageFactory::make(new FrontMatter(['image' => 'http']))->getSource());
    }

    public function testImagePathsWithCacheBusting()
    {
        config(['hyde.cache_busting' => true]);

        $this->assertSame('media/foo?v=accf8b33', (new FeaturedImage('_media/foo', ...$this->defaultArguments()))->getSource());
        $this->assertSame('media/foo?v=accf8b33', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'foo']))->getSource());
        $this->assertSame('media/foo?v=accf8b33', FeaturedImageFactory::make(new FrontMatter(['image.source' => 'media/foo']))->getSource());
        $this->assertSame('media/foo?v=accf8b33', FeaturedImageFactory::make(new FrontMatter(['image.source' => '_media/foo']))->getSource());
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
}

class RemoteImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('https://example.com');
    }
}

class NullImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source');
    }
}

class FilledImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source', 'alt', 'title', 'author', 'authorUrl', 'license', 'licenseUrl', 'copyright');
    }
}
