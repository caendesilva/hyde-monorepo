<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Hyde;
use Hyde\Testing\UnitTestCase;
use Hyde\Framework\Exceptions\FileConflictException;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Exceptions\RouteNotFoundException;
use Hyde\Framework\Exceptions\UnsupportedPageTypeException;
use Hyde\Framework\Exceptions\ParseException;
use RuntimeException;
use Exception;
use Hyde\Framework\Exceptions\InvalidConfigurationException;

/**
 * @covers \Hyde\Framework\Exceptions\FileConflictException
 * @covers \Hyde\Framework\Exceptions\FileNotFoundException
 * @covers \Hyde\Framework\Exceptions\RouteNotFoundException
 * @covers \Hyde\Framework\Exceptions\UnsupportedPageTypeException
 * @covers \Hyde\Framework\Exceptions\InvalidConfigurationException
 * @covers \Hyde\Framework\Exceptions\ParseException
 */
class CustomExceptionsTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    public function testFileConflictExceptionWithDefaultMessage()
    {
        $this->assertSame('A file already exists at this path.', (new FileConflictException())->getMessage());
    }

    public function testFileConflictExceptionWithPath()
    {
        $this->assertSame('File [foo] already exists.', (new FileConflictException('foo'))->getMessage());
    }

    public function testFileConflictExceptionWithAbsolutePath()
    {
        $this->assertSame('File [foo] already exists.', (new FileConflictException(Hyde::path('foo')))->getMessage());
    }

    public function testFileNotFoundExceptionWithDefaultMessage()
    {
        $this->assertSame('File not found.', (new FileNotFoundException())->getMessage());
    }

    public function testFileNotFoundExceptionWithPath()
    {
        $this->assertSame('File [foo] not found.', (new FileNotFoundException('foo'))->getMessage());
    }

    public function testFileNotFoundExceptionWithAbsolutePath()
    {
        $this->assertSame('File [foo] not found.', (new FileNotFoundException(Hyde::path('foo')))->getMessage());
    }

    public function testFileNotFoundExceptionWithCustomPath()
    {
        $this->assertSame('foo', (new FileNotFoundException(customMessage: 'foo'))->getMessage());
    }

    public function testFileNotFoundExceptionWithAppendAfterPath()
    {
        $this->assertSame('File [foo] not found!', (new FileNotFoundException('foo', null, '!'))->getMessage());
    }

    public function testRouteNotFoundExceptionWithDefaultMessage()
    {
        $this->assertSame('Route not found.', (new RouteNotFoundException())->getMessage());
    }

    public function testRouteNotFoundExceptionWithRouteKey()
    {
        $this->assertSame('Route [foo] not found.', (new RouteNotFoundException('foo'))->getMessage());
    }

    public function testUnsupportedPageTypeExceptionWithDefaultMessage()
    {
        $this->assertSame('The page type is not supported.', (new UnsupportedPageTypeException())->getMessage());
    }

    public function testUnsupportedPageTypeExceptionWithPage()
    {
        $this->assertSame('The page type [foo] is not supported.', (new UnsupportedPageTypeException('foo'))->getMessage());
    }

    public function testFileConflictExceptionCode()
    {
        $this->assertSame(409, (new FileConflictException())->getCode());
    }

    public function testFileNotFoundExceptionCode()
    {
        $this->assertSame(404, (new FileNotFoundException())->getCode());
    }

    public function testRouteNotFoundExceptionCode()
    {
        $this->assertSame(404, (new RouteNotFoundException())->getCode());
    }

    public function testUnsupportedPageTypeExceptionCode()
    {
        $this->assertSame(400, (new UnsupportedPageTypeException())->getCode());
    }

    public function testParseExceptionCode()
    {
        $this->assertSame(422, (new ParseException())->getCode());
    }

    public function testParseExceptionWithDefaultMessage()
    {
        $exception = new ParseException();

        $this->assertSame('Invalid data in file', $exception->getMessage());
    }

    public function testParseExceptionWithFileName()
    {
        $exception = new ParseException('example.md');

        $this->assertSame("Invalid Markdown in file: 'example.md'", $exception->getMessage());
    }

    public function testParseExceptionWithFileNameAndCustomMessage()
    {
        $previous = new RuntimeException('Custom error message.');
        $exception = new ParseException('example.yml', $previous);

        $this->assertSame("Invalid Yaml in file: 'example.yml' (Custom error message)", $exception->getMessage());
    }

    public function testParseExceptionWithTxtExtension()
    {
        $exception = new ParseException('example.txt');

        $this->assertSame("Invalid data in file: 'example.txt'", $exception->getMessage());
    }

    public function testParseExceptionWithJsonExtension()
    {
        $exception = new ParseException('example.json');

        $this->assertSame("Invalid Json in file: 'example.json'", $exception->getMessage());
    }

    public function testParseExceptionWithUnsupportedExtension()
    {
        $exception = new ParseException('example.foo');

        $this->assertSame("Invalid data in file: 'example.foo'", $exception->getMessage());
    }

    public function testParseExceptionWithEmptyFileNameAndCustomMessage()
    {
        $previous = new RuntimeException('Custom error message.');
        $exception = new ParseException('', $previous);

        $this->assertSame('Invalid data in file (Custom error message)', $exception->getMessage());
    }

    public function testParseExceptionWithEmptyFileNameAndEmptyPreviousMessage()
    {
        $previous = new RuntimeException('');
        $exception = new ParseException('', $previous);

        $this->assertSame('Invalid data in file', $exception->getMessage());
    }

    public function testParseExceptionWithNoPrevious()
    {
        $exception = new ParseException('example.md');

        $this->assertSame("Invalid Markdown in file: 'example.md'", $exception->getMessage());
        $this->assertNull($exception->getPrevious());
    }

    public function testParseExceptionWithPrevious()
    {
        $previous = new Exception('Parsing error.');
        $exception = new ParseException('example.md', $previous);

        $this->assertSame("Invalid Markdown in file: 'example.md' (Parsing error)", $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidConfigurationExceptionWithDefaultMessage()
    {
        $exception = new InvalidConfigurationException();

        $this->assertSame('Invalid configuration detected.', $exception->getMessage());
    }

    public function testInvalidConfigurationExceptionWithCustomMessage()
    {
        $exception = new InvalidConfigurationException('Custom error message.');

        $this->assertSame('Custom error message.', $exception->getMessage());
    }

    public function testInvalidConfigurationExceptionWithNamespaceAndKey()
    {
        $exception = new InvalidConfigurationException('Invalid configuration.', 'hyde', 'name');

        $this->assertSame('Invalid configuration.', $exception->getMessage());
        $this->assertFileExists($exception->getFile());
        $this->assertIsInt($exception->getLine());

        $this->assertStringContainsString('config'.DIRECTORY_SEPARATOR.'hyde.php', $exception->getFile());
        $this->assertGreaterThan(0, $exception->getLine());
    }

    public function testInvalidConfigurationExceptionWithPreviousThrowable()
    {
        $previous = new Exception('Previous exception.');
        $exception = new InvalidConfigurationException('Invalid configuration.', 'hyde', 'name', $previous);

        $this->assertSame('Invalid configuration.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidConfigurationExceptionTryMethodWithSuccessfulCallback()
    {
        $result = InvalidConfigurationException::try(function () {
            return 'success';
        });

        $this->assertSame('success', $result);
    }

    public function testInvalidConfigurationExceptionTryMethodWithThrowingCallback()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Custom error message');

        InvalidConfigurationException::try(function () {
            throw new RuntimeException('Original error');
        }, 'Custom error message');
    }

    public function testInvalidConfigurationExceptionTryMethodWithDefaultMessage()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Original error');

        InvalidConfigurationException::try(function () {
            throw new RuntimeException('Original error');
        });
    }

    public function testInvalidConfigurationExceptionTryMethodPreservesPreviousException()
    {
        try {
            InvalidConfigurationException::try(function () {
                throw new RuntimeException('Original error');
            }, 'Custom error message');
        } catch (InvalidConfigurationException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
            $this->assertSame('Original error', $e->getPrevious()->getMessage());
        }
    }
}
