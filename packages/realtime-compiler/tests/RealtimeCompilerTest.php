<?php

declare(strict_types=1);

use Hyde\Testing\UnitTestCase;
use Desilva\Microserve\JsonResponse;
use Desilva\Microserve\Request;
use Desilva\Microserve\Response;
use Hyde\Facades\Filesystem;
use Hyde\Framework\Exceptions\RouteNotFoundException;
use Hyde\RealtimeCompiler\Http\ExceptionHandler;
use Desilva\Microserve\HtmlResponse;
use Hyde\RealtimeCompiler\Http\HttpKernel;

class RealtimeCompilerTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('SERVER_LIVE_EDIT=false');

        define('BASE_PATH', realpath(__DIR__.'/../../../'));

        if (BASE_PATH === false || ! file_exists(BASE_PATH.'/hyde')) {
            throw new InvalidArgumentException('This test suite must be run from the root of the hydephp/develop monorepo.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        ob_start();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ob_end_clean();
    }

    public function testHandlesRoutesIndexPage()
    {
        putenv('SERVER_DASHBOARD=false');
        $this->mockRoute('');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('<title>Welcome to HydePHP!</title>', $response->body);
    }

    public function testHandlesRoutesCustomPages()
    {
        $this->mockRoute('foo');

        Filesystem::put('_pages/foo.md', '# Hello World!');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('<h1>Hello World!</h1>', $response->body);

        Filesystem::unlink('_pages/foo.md');
    }

    public function testHandlesRoutesPagesWithHtmlExtension()
    {
        Filesystem::put('_pages/foo.md', '# Hello World!');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('<h1>Hello World!</h1>', $response->body);

        Filesystem::unlink('_pages/foo.md');
    }

    public function testHandlesRoutesStaticAssets()
    {
        $this->mockRoute('media/test.css');
        Filesystem::put('_media/test.css', 'test');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertEquals('test', $response->body);

        Filesystem::unlink('_media/test.css');
    }

    public function testNormalizesMediaPath()
    {
        $this->mockRoute('media/test.css');
        Filesystem::put('_media/test.css', 'test');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertEquals('test', $response->body);

        Filesystem::unlink('_media/test.css');
    }

    public function testThrowsRouteNotFoundExceptionForMissingRoute()
    {
        $this->mockRoute('missing');

        $kernel = new HttpKernel();

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route [missing] not found');

        $kernel->handle(new Request());
    }

    public function testSends404ErrorResponseForMissingAsset()
    {
        $this->mockRoute('missing.css');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->statusCode);
        $this->assertEquals('Not Found', $response->statusMessage);
    }

    public function testTrailingSlashesAreNormalizedFromRoute()
    {
        $this->mockRoute('foo/');

        Filesystem::put('_pages/foo.md', '# Hello World!');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('<h1>Hello World!</h1>', $response->body);

        Filesystem::unlink('_pages/foo.md');
    }

    public function testDocsUriPathIsReroutedToDocsIndex()
    {
        $this->mockRoute('docs');

        Filesystem::put('_docs/index.md', '# Hello World!');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('HydePHP Docs', $response->body);

        Filesystem::unlink('_docs/index.md');
    }

    public function testDocsSearchRendersSearchPage()
    {
        $this->mockRoute('docs/search');
        Filesystem::put('_docs/index.md', '# Hello World!');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
        $this->assertStringContainsString('Search the documentation site', $response->body);

        Filesystem::unlink('_docs/index.md');
    }

    public function testPingRouteReturnsPingResponse()
    {
        $this->mockRoute('ping');

        $kernel = new HttpKernel();
        $response = $kernel->handle(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('OK', $response->statusMessage);
    }

    public function testExceptionHandling()
    {
        $exception = new Exception('foo');
        $response = ExceptionHandler::handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->statusCode);
        $this->assertEquals('Internal Server Error', $response->statusMessage);
    }

    protected function mockRoute(string $route, $method = 'GET'): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = "/$route";
    }
}
