<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Foundation\HydeKernel;
use Illuminate\Support\Collection;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Framework\Features\Blogging\Models\FeaturedImage
 *
 * @see \Hyde\Framework\Testing\Feature\FeaturedImageTest
 */
class FeaturedImageUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    protected const ARGUMENTS = ['alt', 'title', 'author', 'authorUrl', 'copyright', 'license', 'licenseUrl'];

    public function testCanConstruct()
    {
        $this->assertInstanceOf(FeaturedImage::class, new FeaturedImage('foo'));
    }

    public function testGetAltTextWithoutData()
    {
        $this->assertNull((new NullImage)->getAltText());
    }

    public function testGetAltTextWithData()
    {
        $this->assertSame('alt', (new FilledImage)->getAltText());
    }

    public function testGetTitleTextWithoutData()
    {
        $this->assertNull((new NullImage)->getTitleText());
    }

    public function testGetTitleTextWithData()
    {
        $this->assertSame('title', (new FilledImage)->getTitleText());
    }

    public function testGetAuthorNameWithoutData()
    {
        $this->assertNull((new NullImage)->getAuthorName());
    }

    public function testGetAuthorNameWithData()
    {
        $this->assertSame('author', (new FilledImage)->getAuthorName());
    }

    public function testGetAuthorUrlWithoutData()
    {
        $this->assertNull((new NullImage)->getAuthorUrl());
    }

    public function testGetAuthorUrlWithData()
    {
        $this->assertSame('authorUrl', (new FilledImage)->getAuthorUrl());
    }

    public function testGetCopyrightTextWithoutData()
    {
        $this->assertNull((new NullImage)->getCopyrightText());
    }

    public function testGetCopyrightTextWithData()
    {
        $this->assertSame('copyright', (new FilledImage)->getCopyrightText());
    }

    public function testGetLicenseNameWithoutData()
    {
        $this->assertNull((new NullImage)->getLicenseName());
    }

    public function testGetLicenseNameWithData()
    {
        $this->assertSame('license', (new FilledImage)->getLicenseName());
    }

    public function testGetLicenseUrlWithoutData()
    {
        $this->assertNull((new NullImage)->getLicenseUrl());
    }

    public function testGetLicenseUrlWithData()
    {
        $this->assertSame('licenseUrl', (new FilledImage)->getLicenseUrl());
    }

    public function testHasAltTextWithoutData()
    {
        $this->assertFalse((new NullImage)->hasAltText());
    }

    public function testHasAltTextWithData()
    {
        $this->assertTrue((new FilledImage)->hasAltText());
    }

    public function testHasTitleTextWithoutData()
    {
        $this->assertFalse((new NullImage)->hasTitleText());
    }

    public function testHasTitleTextWithData()
    {
        $this->assertTrue((new FilledImage)->hasTitleText());
    }

    public function testHasAuthorNameWithoutData()
    {
        $this->assertFalse((new NullImage)->hasAuthorName());
    }

    public function testHasAuthorNameWithData()
    {
        $this->assertTrue((new FilledImage)->hasAuthorName());
    }

    public function testHasAuthorUrlWithoutData()
    {
        $this->assertFalse((new NullImage)->hasAuthorUrl());
    }

    public function testHasAuthorUrlWithData()
    {
        $this->assertTrue((new FilledImage)->hasAuthorUrl());
    }

    public function testHasCopyrightTextWithoutData()
    {
        $this->assertFalse((new NullImage)->hasCopyrightText());
    }

    public function testHasCopyrightTextWithData()
    {
        $this->assertTrue((new FilledImage)->hasCopyrightText());
    }

    public function testHasLicenseNameWithoutData()
    {
        $this->assertFalse((new NullImage)->hasLicenseName());
    }

    public function testHasLicenseNameWithData()
    {
        $this->assertTrue((new FilledImage)->hasLicenseName());
    }

    public function testHasLicenseUrlWithoutData()
    {
        $this->assertFalse((new NullImage)->hasLicenseUrl());
    }

    public function testHasLicenseUrlWithData()
    {
        $this->assertTrue((new FilledImage)->hasLicenseUrl());
    }

    public function testGetTypeForLocalImage()
    {
        $this->assertSame('local', (new LocalImage)->getType());
    }

    public function testGetTypeForRemoteImage()
    {
        $this->assertSame('remote', (new RemoteImage)->getType());
    }

    public function testGetContentLength()
    {
        $this->assertSame(0, (new NullImage)->getContentLength());
        $this->assertSame(0, (new FilledImage)->getContentLength());
    }

    public function testFeaturedImageGetContentLengthWithNoSource()
    {
        HydeKernel::setInstance(new class extends HydeKernel
        {
            public function assets(): Collection
            {
                return new Collection();
            }
        });

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [_media/foo] not found when trying to resolve a media asset.');

        $image = new FeaturedImage('_media/foo', ...self::ARGUMENTS);
        $this->assertSame(0, $image->getContentLength());
    }

    public function testCanConstructFeaturedImageWithRemoteSource()
    {
        $image = new FeaturedImage('http/foo', ...self::ARGUMENTS);

        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('http/foo', $image->getSource());
    }

    public function testCanConstructFeaturedImageWithHttps()
    {
        $image = new FeaturedImage('https/foo', ...self::ARGUMENTS);

        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('https/foo', $image->getSource());
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

    public function getContentLength(): int
    {
        return 0;
    }
}

class FilledImage extends FeaturedImage
{
    public function __construct()
    {
        parent::__construct('source', 'alt', 'title', 'author', 'authorUrl', 'license', 'licenseUrl', 'copyright');
    }

    public function getContentLength(): int
    {
        return 0;
    }
}
