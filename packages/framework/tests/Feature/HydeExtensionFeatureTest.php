<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use BadMethodCallException;
use Hyde\Foundation\Concerns\HydeExtension;
use Hyde\Foundation\Facades\Files;
use Hyde\Foundation\Facades\Pages;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Kernel\FileCollection;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Foundation\Kernel\RouteCollection;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Filesystem\SourceFile;
use Hyde\Support\Models\Route;
use Hyde\Testing\TestCase;
use InvalidArgumentException;
use stdClass;

/**
 * Tests the Extensions API Feature on a higher level to ensure the components work together.
 *
 * @covers \Hyde\Foundation\Concerns\HydeExtension
 * @covers \Hyde\Foundation\Concerns\ManagesExtensions
 * @covers \Hyde\Foundation\HydeKernel
 * @covers \Hyde\Foundation\Kernel\FileCollection
 * @covers \Hyde\Foundation\Kernel\PageCollection
 * @covers \Hyde\Foundation\Kernel\RouteCollection
 *
 * @see \Hyde\Framework\Testing\Unit\ExtensionsUnitTest
 */
class HydeExtensionFeatureTest extends TestCase
{
    protected HydeKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = HydeKernel::getInstance();
    }

    public function testHandlerMethodsAreCalledByDiscovery()
    {
        $this->kernel->registerExtension(HydeTestExtension::class);

        $this->assertSame([], HydeTestExtension::$callCache);

        $this->kernel->boot();

        $this->assertSame(['booting', 'files', 'pages', 'routes', 'booted'], HydeTestExtension::$callCache);

        HydeTestExtension::$callCache = [];
    }

    public function testFileHandlerDependencyInjection()
    {
        $this->kernel->registerExtension(InspectableTestExtension::class);
        $this->kernel->boot();

        $this->assertInstanceOf(FileCollection::class, ...InspectableTestExtension::getCalled('files'));
    }

    public function testPageHandlerDependencyInjection()
    {
        $this->kernel->registerExtension(InspectableTestExtension::class);
        $this->kernel->boot();

        $this->assertInstanceOf(PageCollection::class, ...InspectableTestExtension::getCalled('pages'));
    }

    public function testRouteHandlerDependencyInjection()
    {
        $this->kernel->registerExtension(InspectableTestExtension::class);
        $this->kernel->boot();

        $this->assertInstanceOf(RouteCollection::class, ...InspectableTestExtension::getCalled('routes'));
    }

    public function testRegisterExtensionMethodThrowsExceptionWhenKernelIsAlreadyBooted()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot register an extension after the Kernel has been booted.');

        app(HydeKernel::class)->boot();
        app(HydeKernel::class)->registerExtension(HydeTestExtension::class);
    }

    public function testRegisterExtensionMethodOnlyAcceptsInstancesOfHydeExtension()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Extension [stdClass] must extend the HydeExtension class.');

        app(HydeKernel::class)->registerExtension(stdClass::class);
    }

    public function testCustomRegisteredPagesAreDiscoveredByTheFileCollectionClass()
    {
        app(HydeKernel::class)->registerExtension(TestPageExtension::class);
        FileCollection::init(app(HydeKernel::class))->boot();

        $this->directory('foo');
        $this->file('foo/bar.txt');

        $this->assertArrayHasKey('foo/bar.txt', Files::all());
        $this->assertEquals(new SourceFile('foo/bar.txt', TestPageClass::class), Files::get('foo/bar.txt'));
    }

    public function testCustomRegisteredPagesAreDiscoveredByThePageCollectionClass()
    {
        $this->directory('foo');
        $this->file('foo/bar.txt');

        app(HydeKernel::class)->registerExtension(TestPageExtension::class);
        PageCollection::init(app(HydeKernel::class))->boot();

        $this->assertArrayHasKey('foo/bar.txt', Pages::all());
        $this->assertEquals(new TestPageClass('bar'), Pages::get('foo/bar.txt'));
    }

    public function testCustomRegisteredPagesAreDiscoveredByTheRouteCollectionClass()
    {
        $this->directory('foo');
        $this->file('foo/bar.txt');

        app(HydeKernel::class)->registerExtension(TestPageExtension::class);
        RouteCollection::init(app(HydeKernel::class))->boot();

        $this->assertArrayHasKey('foo/bar', Routes::all());
        $this->assertEquals(new Route(new TestPageClass('bar')), Routes::get('foo/bar'));
    }

    public function testBootingAndBootedMethodsAreCalledWithKernel()
    {
        $this->kernel->registerExtension(BootableTestExtension::class);

        $this->assertSame([], BootableTestExtension::$callCache);

        $this->kernel->boot();

        $this->assertCount(2, BootableTestExtension::$callCache);

        $this->assertInstanceOf(HydeKernel::class, BootableTestExtension::$callCache['booting']);
        $this->assertInstanceOf(HydeKernel::class, BootableTestExtension::$callCache['booted']);
        $this->assertSame($this->kernel, BootableTestExtension::$callCache['booting']);
        $this->assertSame($this->kernel, BootableTestExtension::$callCache['booted']);

        BootableTestExtension::$callCache = [];
    }

    protected function markTestSuccessful(): void
    {
        $this->assertTrue(true);
    }
}

class HydeTestExtension extends HydeExtension
{
    // An easy way to assert the handlers are called.
    public static array $callCache = [];

    public static function getPageClasses(): array
    {
        return [
            HydeExtensionTestPage::class,
        ];
    }

    public function discoverFiles(FileCollection $collection): void
    {
        static::$callCache[] = 'files';
    }

    public function discoverPages(PageCollection $collection): void
    {
        static::$callCache[] = 'pages';
    }

    public function discoverRoutes(RouteCollection $collection): void
    {
        static::$callCache[] = 'routes';
    }

    public function booting(HydeKernel $kernel): void
    {
        static::$callCache[] = 'booting';
    }

    public function booted(HydeKernel $kernel): void
    {
        static::$callCache[] = 'booted';
    }
}

class HydeExtensionTestPage extends HydePage
{
    public static string $sourceDirectory = 'foo';
    public static string $outputDirectory = 'foo';
    public static string $fileExtension = '.txt';

    public function compile(): string
    {
        return '';
    }
}

class TestPageClass extends HydePage
{
    public static string $sourceDirectory = 'foo';
    public static string $outputDirectory = 'foo';
    public static string $fileExtension = '.txt';

    public function compile(): string
    {
        return '';
    }
}

class TestPageExtension extends HydeExtension
{
    public static function getPageClasses(): array
    {
        return [
            TestPageClass::class,
        ];
    }
}

class InspectableTestExtension extends HydeExtension
{
    private static array $callCache = [];

    public function discoverFiles(FileCollection $collection): void
    {
        self::$callCache['files'] = func_get_args();
    }

    public function discoverPages(PageCollection $collection): void
    {
        self::$callCache['pages'] = func_get_args();
    }

    public function discoverRoutes(RouteCollection $collection): void
    {
        self::$callCache['routes'] = func_get_args();
    }

    public static function getCalled(string $method): array
    {
        return self::$callCache[$method];
    }
}

class BootableTestExtension extends HydeExtension
{
    public static array $callCache = [];

    public function booting(HydeKernel $kernel): void
    {
        static::$callCache['booting'] = $kernel;
    }

    public function booted(HydeKernel $kernel): void
    {
        static::$callCache['booted'] = $kernel;
    }
}
