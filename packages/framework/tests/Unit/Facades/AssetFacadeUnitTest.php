<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Facades;

use Hyde\Hyde;
use Hyde\Facades\Asset;
use Hyde\Testing\UnitTestCase;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\RenderData;
use Hyde\Framework\Exceptions\FileNotFoundException;

/**
 * @covers \Hyde\Facades\Asset
 */
class AssetFacadeUnitTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::setupKernel();
        self::mockConfig();

        Render::swap(new RenderData());
    }

    public function testGetHelper()
    {
        $this->assertSame(Hyde::asset('app.css'), Asset::get('app.css'));
    }

    public function testGetHelperWithNonExistentFile()
    {
        $this->expectException(FileNotFoundException::class);
        Asset::get('styles.css');
    }

    public function testHasMediaFileHelper()
    {
        $this->assertFalse(Asset::exists('styles.css'));
    }

    public function testHasMediaFileHelperReturnsTrueForExistingFile()
    {
        $this->assertTrue(Asset::exists('app.css'));
    }

    public function testAssetReturnsMediaPathWithCacheKey()
    {
        $this->assertIsString($path = (string) Asset::get('app.css'));
        $this->assertSame('media/app.css?v='.hash_file('crc32', Hyde::path('_media/app.css')), $path);
    }

    public function testAssetReturnsMediaPathWithoutCacheKeyIfCacheBustingIsDisabled()
    {
        self::mockConfig(['hyde.cache_busting' => false]);

        $path = (string) Asset::get('app.css');

        $this->assertIsString($path);
        $this->assertSame('media/app.css', $path);
    }
}
