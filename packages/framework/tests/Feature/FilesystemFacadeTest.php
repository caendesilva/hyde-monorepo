<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Facades\Filesystem
 * @covers \Hyde\Foundation\Kernel\Filesystem
 * @covers \Hyde\Framework\Concerns\Internal\ForwardsIlluminateFilesystem
 *
 * @see \FilesystemFacadeMimeTypeHelperUnitTest
 * @see \Hyde\Framework\Testing\Unit\FilesystemFacadeUnitTest
 */
class FilesystemFacadeTest extends TestCase
{
    public function testBasePath()
    {
        $this->assertSame(Hyde::path(), Filesystem::basePath());
    }

    public function testAbsolutePath()
    {
        $this->assertSame(Hyde::path('foo'), Filesystem::absolutePath('foo'));
        $this->assertSame(Hyde::path('foo'), Filesystem::absolutePath(Hyde::path('foo')));
    }

    public function testRelativePath()
    {
        $this->assertSame('', Filesystem::relativePath(Hyde::path()));
        $this->assertSame('foo', Filesystem::relativePath(Hyde::path('foo')));
        $this->assertSame('foo', Filesystem::relativePath('foo'));
    }

    public function testTouch()
    {
        Filesystem::touch('foo');

        $this->assertFileExists(Hyde::path('foo'));

        Filesystem::unlink('foo');
    }

    public function testUnlink()
    {
        touch(Hyde::path('foo'));

        Filesystem::unlink('foo');

        $this->assertFileDoesNotExist(Hyde::path('foo'));
    }

    public function testUnlinkIfExists()
    {
        touch(Hyde::path('foo'));

        Filesystem::unlinkIfExists('foo');

        $this->assertFileDoesNotExist(Hyde::path('foo'));
    }

    public function testMethodWithoutMocking()
    {
        $this->assertSame(3, Filesystem::put('foo', 'bar'));
        $this->assertFileExists(Hyde::path('foo'));

        unlink(Hyde::path('foo'));
    }

    public function testMethodWithNamedArguments()
    {
        $this->assertSame(3, Filesystem::put(path: 'foo', contents: 'bar'));
        $this->assertFileExists(Hyde::path('foo'));

        unlink(Hyde::path('foo'));
    }

    public function testMethodWithMixedSequentialAndNamedArguments()
    {
        $this->assertSame(3, Filesystem::put('foo', contents: 'bar'));
        $this->assertFileExists(Hyde::path('foo'));

        unlink(Hyde::path('foo'));
    }

    public function testMethodWithMixedSequentialAndNamedArgumentsSkippingMiddleOne()
    {
        Filesystem::makeDirectory('foo', recursive: true);

        $this->assertDirectoryExists(Hyde::path('foo'));

        rmdir(Hyde::path('foo'));
    }

    public function testFindMimeTypeLookup()
    {
        $this->assertSame('text/plain', Filesystem::findMimeType('test.txt'));
    }

    public function testFindMimeTypeWithKnownFile()
    {
        $this->file('test.txt', 'This is a test file.');
        $this->assertSame('text/plain', Filesystem::findMimeType('test.txt'));
    }

    public function testFindMimeTypeWithUnknownExtension()
    {
        $this->file('text.unknown', 'text');
        $this->assertSame('text/plain', Filesystem::findMimeType('text.unknown'));
    }

    public function testFindMimeTypeWithBlankFile()
    {
        $this->file('blank.unknown', '');
        $this->assertSame('application/x-empty', Filesystem::findMimeType('blank.unknown'));
    }

    public function testFindMimeTypeWithEmptyFile()
    {
        $this->file('empty.unknown');
        $this->assertSame('application/x-empty', Filesystem::findMimeType('empty.unknown'));
    }

    public function testFindMimeTypeWithJsonFile()
    {
        $this->file('json.unknown', '{"key": "value"}');
        $this->assertSame('application/json', Filesystem::findMimeType('json.unknown'));
    }

    public function testFindMimeTypeWithXmlFile()
    {
        $this->file('xml.unknown', '<?xml version="1.0" encoding="UTF-8"?><root></root>');
        $this->assertSame('text/xml', Filesystem::findMimeType('xml.unknown'));
    }

    public function testFindMimeTypeWithHtmlFile()
    {
        $this->file('html.unknown', '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>');
        $this->assertSame('text/html', Filesystem::findMimeType('html.unknown'));
    }

    public function testFindMimeTypeWithYamlFile()
    {
        $this->file('yaml.unknown', 'key: value');
        $this->assertSame('text/plain', Filesystem::findMimeType('yaml.unknown')); // YAML is not detected by fileinfo
    }

    public function testFindMimeTypeWithCssFile()
    {
        $this->file('css.unknown', 'body { color: red; }');
        $this->assertSame('text/plain', Filesystem::findMimeType('css.unknown')); // CSS is not detected by fileinfo
    }

    public function testFindMimeTypeWithJsFile()
    {
        $this->file('js.unknown', 'console.log("Hello, World!");');
        $this->assertSame('text/plain', Filesystem::findMimeType('js.unknown')); // JavaScript is not detected by fileinfo
    }

    public function testFindMimeTypeWithBinaryFile()
    {
        $this->file('binary.unknown', "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F"); // 16 bytes of binary data
        $this->assertSame('application/octet-stream', Filesystem::findMimeType('binary.unknown'));
    }

    public function testFindMimeTypeWithPngFile()
    {
        $this->file('png.unknown', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAvM1P4AAAAASUVORK5CYII=')); // 1x1 transparent PNG
        $this->assertSame('image/png', Filesystem::findMimeType('png.unknown'));
    }

    public function testFindMimeTypeWithJpegFile()
    {
        $this->file('jpeg.unknown', base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/4QA6RXhpZgAATU0AKgAAAAgAA1IBAAABAAAAngIBAAABAAAAnwICAAABAAAAnQ==')); // 1x1 JPEG
        $this->assertSame('image/jpeg', Filesystem::findMimeType('jpeg.unknown'));
    }

    public function testFindMimeTypeWithGifFile()
    {
        $this->file('gif.unknown', base64_decode('R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=')); // 1x1 GIF
        $this->assertSame('image/gif', Filesystem::findMimeType('gif.unknown'));
    }

    public function testFindMimeTypeWithNonExistingFile()
    {
        $this->assertSame('text/plain', Filesystem::findMimeType('non_existing_file.txt'));
    }

    public function testFindMimeTypeUsesLookupBeforeFileinfo()
    {
        $this->file('file.png', 'Not PNG content');
        $this->assertSame('image/png', Filesystem::findMimeType('file.png'));

        $this->file('png.txt', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAvM1P4AAAAASUVORK5CYII=')); // 1x1 transparent PNG
        $this->assertSame('text/plain', Filesystem::findMimeType('png.txt'));
    }

    protected function createExpectation(string $method, mixed $returns, ...$args): void
    {
        File::shouldReceive($method)->withArgs($args)->once()->andReturn($returns);
    }
}
