<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use BadMethodCallException;
use Hyde\Foundation\HydeKernel;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Symfony\Component\Yaml\Yaml;
use Hyde\Support\Facades\Render;
use Hyde\Foundation\Facades\Routes;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Framework\Exceptions\FileNotFoundException;

/**
 * Covers the helpers in helpers.php.
 *
 * @see \Hyde\Framework\Testing\Unit\UnixsumTest for additional tests of the unixsum function
 */
class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hyde.cache_busting' => false]);
    }

    /** @covers ::hyde */
    public function testHydeFunctionExists()
    {
        $this->assertTrue(function_exists('hyde'));
    }

    /** @covers ::hyde */
    public function testHydeFunctionReturnsHydeKernelClass()
    {
        $this->assertInstanceOf(HydeKernel::class, hyde());
    }

    /** @covers ::hyde */
    public function testCanCallMethodsOnReturnedHydeClass()
    {
        $this->assertSame(Hyde::path(), hyde()->path());
    }

    /** @covers ::\Hyde\unslash */
    public function testUnslashFunctionExists()
    {
        $this->assertTrue(function_exists('Hyde\unslash'));
    }

    /** @covers ::\Hyde\unslash */
    public function testUnslashFunctionTrimsTrailingSlashes()
    {
        $tests = ['foo',  '/foo',  'foo/',  '/foo/',  '\foo\\',  '\\/foo/\\'];

        foreach ($tests as $test) {
            $this->assertSame('foo', \Hyde\unslash($test));
        }

        $tests = ['',  '/',  '\\',  '/\\'];

        foreach ($tests as $test) {
            $this->assertSame('', \Hyde\unslash($test));
        }

        $tests = ['foo/bar',  'foo/bar/',  'foo/bar\\',  '\\/foo/bar/\\'];

        foreach ($tests as $test) {
            $this->assertSame('foo/bar', \Hyde\unslash($test));
        }
    }

    /** @covers ::asset */
    public function testAssetFunction()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        $this->assertSame(Hyde::asset('app.css'), asset('app.css'));
        $this->assertSame('media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionWithCacheBusting()
    {
        config(['hyde.cache_busting' => true]);

        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        $this->assertSame(Hyde::asset('app.css'), asset('app.css'));
        $this->assertSame(
            'media/app.css?v='.hash_file('crc32', Hyde::path('_media/app.css')),
            (string) asset('app.css')
        );
    }

    /** @covers ::asset */
    public function testAssetFunctionWithExternalUrl()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [_media/https://example.com/foo] not found when trying to resolve a media asset.');
        $this->assertSame('https://example.com/foo', asset('https://example.com/foo'));
    }

    /** @covers ::asset */
    public function testAssetFunctionWithSetBaseUrl()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        $this->app['config']->set(['hyde.url' => 'https://example.com']);
        $this->assertSame('https://example.com/media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionWithNoBaseUrl()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        $this->app['config']->set(['hyde.url' => null]);
        $this->assertSame('media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionWithLocalhostBaseUrl()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        $this->app['config']->set(['hyde.url' => 'http://localhost']);
        $this->assertSame('media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionFromNestedPage()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        Render::shouldReceive('getRouteKey')->andReturn('foo/bar');

        $this->assertSame('../media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionFromDeeplyNestedPage()
    {
        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('media/app.css'), asset('app.css'));

        Render::shouldReceive('getRouteKey')->andReturn('foo/bar/baz');

        $this->assertSame('../../media/app.css', (string) asset('app.css'));
    }

    /** @covers ::asset */
    public function testAssetFunctionWithCustomMediaDirectory()
    {
        $this->file('custom/app.css');
        Hyde::setMediaDirectory('custom');

        $this->assertInstanceOf(MediaFile::class, asset('app.css'));
        $this->assertEquals(new MediaFile('custom/app.css'), asset('app.css'));

        $this->assertSame('custom/app.css', (string) asset('app.css'));
    }

    /** @covers ::route */
    public function testRouteFunction()
    {
        $this->assertNotNull(Hyde::route('index'));
        $this->assertSame(Routes::get('index'), route('index'));
    }

    /** @covers ::route */
    public function testRouteFunctionWithInvalidRoute()
    {
        $this->expectException(\Hyde\Framework\Exceptions\RouteNotFoundException::class);

        route('invalid');
    }

    /** @covers ::url */
    public function testUrlFunction()
    {
        $this->assertSame(Hyde::url('foo'), url('foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithBaseUrl()
    {
        $this->app['config']->set(['hyde.url' => 'https://example.com']);
        $this->assertSame('https://example.com/foo', url('foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithLocalhostBaseUrl()
    {
        $this->app['config']->set(['hyde.url' => 'http://localhost']);
        $this->assertSame('foo', url('foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithoutBaseUrl()
    {
        $this->app['config']->set(['hyde.url' => null]);
        $this->assertSame('foo', url('foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithoutBaseUrlOrPath()
    {
        $this->app['config']->set(['hyde.url' => null]);
        $this->expectException(BadMethodCallException::class);
        $this->assertNull(url());
    }

    /** @covers ::url */
    public function testUrlFunctionWithLocalhostBaseUrlButNoPath()
    {
        $this->app['config']->set(['hyde.url' => 'http://localhost']);
        $this->expectException(BadMethodCallException::class);
        $this->assertNull(url());
    }

    /** @covers ::url */
    public function testUrlFunctionWithAlreadyQualifiedUrl()
    {
        $this->assertSame('https://example.com/foo', url('https://example.com/foo'));
        $this->assertSame('http://localhost/foo', url('http://localhost/foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithAlreadyQualifiedUrlWhenSiteUrlIsSet()
    {
        $this->app['config']->set(['hyde.url' => 'https://example.com']);

        $this->assertSame('https://example.com/foo', url('https://example.com/foo'));
        $this->assertSame('http://localhost/foo', url('http://localhost/foo'));
    }

    /** @covers ::url */
    public function testUrlFunctionWithAlreadyQualifiedUrlWhenSiteUrlIsSetToSomethingElse()
    {
        $this->app['config']->set(['hyde.url' => 'my-site.com']);

        $this->assertSame('https://example.com/foo', url('https://example.com/foo'));
        $this->assertSame('http://localhost/foo', url('http://localhost/foo'));
    }

    /** @covers ::\Hyde\hyde */
    public function testHydeFunctionExistsInHydeNamespace()
    {
        $this->assertTrue(function_exists('Hyde\hyde'));
    }

    /** @covers ::\Hyde\hyde */
    public function testNamespacedHydeFunction()
    {
        $this->assertSame(hyde(), \Hyde\hyde());
    }

    /** @covers ::\Hyde\unslash */
    public function testUnslashFunctionExistsInHydeNamespace()
    {
        $this->assertTrue(function_exists('Hyde\unslash'));
    }

    /** @covers ::\Hyde\unslash */
    public function testNamespacedUnslashFunction()
    {
        $this->assertSame(\Hyde\unslash('foo'), \Hyde\unslash('foo'));
    }

    /** @covers ::\Hyde\unixsum */
    public function testUnixsumFunction()
    {
        $this->assertSame(md5("foo\n"), \Hyde\unixsum("foo\n"));
        $this->assertSame(md5("foo\n"), \Hyde\unixsum("foo\r\n"));
    }

    /** @covers ::\Hyde\unixsum_file */
    public function testUnixsumFileFunction()
    {
        $this->file('unix.txt', "foo\n");
        $this->file('windows.txt', "foo\r\n");

        $this->assertSame(md5("foo\n"), \Hyde\unixsum_file('unix.txt'));
        $this->assertSame(md5("foo\n"), \Hyde\unixsum_file('windows.txt'));
    }

    /** @covers ::\Hyde\make_title */
    public function testHydeMakeTitleFunction()
    {
        $this->assertSame(Hyde::makeTitle('foo'), \Hyde\make_title('foo'));
    }

    /** @covers ::\Hyde\normalize_newlines */
    public function testHydeNormalizeNewlinesFunction()
    {
        $this->assertSame(Hyde::normalizeNewlines('foo'), \Hyde\normalize_newlines('foo'));
    }

    /** @covers ::\Hyde\strip_newlines */
    public function testHydeStripNewlinesFunction()
    {
        $this->assertSame(Hyde::stripNewlines('foo'), \Hyde\strip_newlines('foo'));
    }

    /** @covers ::\Hyde\trim_slashes */
    public function testHydeTrimSlashesFunction()
    {
        $this->assertSame(Hyde::trimSlashes('foo'), \Hyde\trim_slashes('foo'));
    }

    /** @covers ::\Hyde\evaluate_arrayable */
    public function testHydeEvaluateArrayableFunction()
    {
        $this->assertSame(['foo'], \Hyde\evaluate_arrayable(['foo']));
        $this->assertSame(['foo'], \Hyde\evaluate_arrayable(collect(['foo'])));
    }

    /** @covers ::\Hyde\yaml_encode */
    public function testHydeYamlEncodeFunction()
    {
        $this->assertSame("foo: bar\n", \Hyde\yaml_encode(['foo' => 'bar']));
    }

    /** @covers ::\Hyde\yaml_encode */
    public function testHydeYamlEncodeFunctionEncodesArrayables()
    {
        $this->assertSame("foo: bar\n", \Hyde\yaml_encode(collect(['foo' => 'bar'])));
    }

    /** @covers ::\Hyde\yaml_encode */
    public function testHydeYamlEncodeFunctionAcceptsParameters()
    {
        $this->assertSame(
            Yaml::dump(['foo' => 'bar'], 4, 2, 128),
            \Hyde\yaml_encode(['foo' => 'bar'], 4, 2, 128)
        );
    }

    /** @covers ::\Hyde\yaml_decode */
    public function testHydeYamlDecodeFunction()
    {
        $this->assertSame(['foo' => 'bar'], \Hyde\yaml_decode("foo: bar\n"));
    }

    /** @covers ::\Hyde\yaml_decode */
    public function testHydeYamlDecodeFunctionAcceptsParameters()
    {
        $this->assertSame(
            Yaml::parse('foo: bar', 128),
            \Hyde\yaml_decode('foo: bar', 128)
        );
    }

    /** @covers ::\Hyde\path_join */
    public function testHydePathJoinFunction()
    {
        $this->assertSame('foo/bar', \Hyde\path_join('foo', 'bar'));
    }

    /** @covers ::\Hyde\path_join */
    public function testHydePathJoinFunctionWithMultiplePaths()
    {
        $this->assertSame('foo/bar/baz', \Hyde\path_join('foo', 'bar', 'baz'));
    }

    /** @covers ::\Hyde\normalize_slashes */
    public function testHydeNormalizeSlashesFunction()
    {
        $this->assertSame('foo/bar', \Hyde\normalize_slashes('foo\\bar'));
        $this->assertSame('foo/bar', \Hyde\normalize_slashes('foo/bar'));
    }
}
