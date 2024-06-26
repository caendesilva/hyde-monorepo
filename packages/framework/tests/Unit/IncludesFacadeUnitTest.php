<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Mockery;
use Closure;
use Hyde\Hyde;
use Hyde\Support\Includes;
use Hyde\Testing\UnitTestCase;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use Illuminate\Filesystem\Filesystem;

/**
 * @covers \Hyde\Support\Includes
 *
 * @see \Hyde\Framework\Testing\Feature\IncludesFacadeTest
 */
class IncludesFacadeUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    protected function setUp(): void
    {
        parent::setUp();

        Blade::swap(Mockery::mock());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testPathReturnsTheIncludesDirectory()
    {
        $this->assertSame(Hyde::path('resources/includes'), Includes::path());
    }

    public function testPathReturnsAPartialWithinTheIncludesDirectory()
    {
        $this->assertSame(Hyde::path('resources/includes/partial.html'), Includes::path('partial.html'));
    }

    public function testGetReturnsPartial()
    {
        $filename = 'foo.txt';
        $expected = 'foo bar';

        $this->mockFilesystem(function ($filesystem) use ($expected, $filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($expected);
        });

        $this->assertSame($expected, Includes::get($filename));
    }

    public function testGetReturnsDefaultValueWhenNotFound()
    {
        $filename = 'foo.txt';
        $default = 'default';

        $this->mockFilesystem(function ($filesystem) use ($filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(false);
        });

        $this->assertNull(Includes::get($filename));
        $this->assertSame($default, Includes::get($filename, $default));
    }

    public function testHtmlReturnsRenderedPartial()
    {
        $filename = 'foo.html';
        $expected = '<h1>foo bar</h1>';

        $this->mockFilesystem(function ($filesystem) use ($expected, $filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($expected);
        });

        $this->assertHtmlStringIsSame($expected, Includes::html($filename));
    }

    public function testHtmlReturnsDefaultValueWhenNotFound()
    {
        $filename = 'foo.html';
        $default = '<h1>default</h1>';

        $this->mockFilesystem(function ($filesystem) use ($filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(false);
        });

        $this->assertNull(Includes::html($filename));
        $this->assertHtmlStringIsSame($default, Includes::html($filename, $default));
    }

    public function testHtmlWithAndWithoutExtension()
    {
        $this->mockFilesystem(function ($filesystem) {
            $filename = 'foo.html';
            $content = '<h1>foo bar</h1>';

            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($content);
        });

        $this->assertHtmlStringIsSame(Includes::html('foo.html'), Includes::html('foo'));
    }

    public function testMarkdownReturnsRenderedPartial()
    {
        $filename = 'foo.md';
        $expected = '<h1>foo bar</h1>';

        $this->mockFilesystem(function ($filesystem) use ($filename) {
            $content = '# foo bar';

            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($content);
        });

        $this->assertHtmlStringIsSame($expected, Includes::markdown($filename));
    }

    public function testMarkdownReturnsRenderedDefaultValueWhenNotFound()
    {
        $filename = 'foo.md';
        $default = '# default';
        $expected = '<h1>default</h1>';

        $this->mockFilesystem(function ($filesystem) use ($filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(false);
        });

        $this->assertNull(Includes::markdown($filename));
        $this->assertHtmlStringIsSame($expected, Includes::markdown($filename, $default));
    }

    public function testMarkdownWithAndWithoutExtension()
    {
        $expected = '<h1>foo bar</h1>';

        $this->mockFilesystem(function ($filesystem) {
            $content = '# foo bar';
            $filename = 'foo.md';

            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($content);
        });

        $this->assertHtmlStringIsSame($expected, Includes::markdown('foo.md'));
        $this->assertHtmlStringIsSame(Includes::markdown('foo.md'), Includes::markdown('foo'));
        $this->assertHtmlStringIsSame(Includes::markdown('foo.md'), Includes::markdown('foo.md'));
    }

    public function testBladeReturnsRenderedPartial()
    {
        $filename = 'foo.blade.php';
        $expected = 'foo bar';

        $this->mockFilesystem(function ($filesystem) use ($expected, $filename) {
            $content = '{{ "foo bar" }}';

            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($content);

            Blade::shouldReceive('render')->with($content)->andReturn($expected);
        });

        $this->assertHtmlStringIsSame($expected, Includes::blade($filename));
    }

    public function testBladeWithAndWithoutExtension()
    {
        $this->mockFilesystem(function ($filesystem) {
            $expected = 'foo bar';
            $content = '{{ "foo bar" }}';
            $filename = 'foo.blade.php';

            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(true);
            $filesystem->shouldReceive('get')->with($this->includesPath($filename))->andReturn($content);

            Blade::shouldReceive('render')->with($content)->andReturn($expected);
        });

        $this->assertHtmlStringIsSame(Includes::blade('foo.blade.php'), Includes::blade('foo'));
    }

    public function testBladeReturnsDefaultValueWhenNotFound()
    {
        $filename = 'foo.blade.php';
        $default = '{{ "default" }}';
        $expected = 'default';

        $this->mockFilesystem(function ($filesystem) use ($default, $expected, $filename) {
            $filesystem->shouldReceive('exists')->with($this->includesPath($filename))->andReturn(false);

            Blade::shouldReceive('render')->with($default)->andReturn($expected);
        });

        $this->assertNull(Includes::blade($filename));
        $this->assertHtmlStringIsSame($expected, Includes::blade($filename, $default));
    }

    protected function mockFilesystem(Closure $config): void
    {
        $filesystem = Mockery::mock(Filesystem::class);

        $config($filesystem);

        app()->instance(Filesystem::class, $filesystem);
    }

    protected function includesPath(string $filename): string
    {
        return Hyde::path('resources/includes/'.$filename);
    }

    protected function assertHtmlStringIsSame(string|HtmlString $expected, mixed $actual): void
    {
        $this->assertInstanceOf(HtmlString::class, $actual);
        $this->assertSame((string) $expected, $actual->toHtml());
    }
}
