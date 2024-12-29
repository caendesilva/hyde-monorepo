<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Mockery;
use Hyde\Testing\UnitTestCase;
use Hyde\Markdown\Models\Markdown;
use Hyde\Framework\Services\MarkdownService;

/**
 * @covers \Hyde\Markdown\Models\Markdown
 */
class MarkdownFacadeTest extends UnitTestCase
{
    public function testRender(): void
    {
        $mock = Mockery::mock(MarkdownService::class);
        $mock->shouldReceive('parse')->once()->andReturn("<h1>Hello World!</h1>\n");
        app()->bind(MarkdownService::class, fn () => $mock);

        $html = Markdown::render('# Hello World!');

        $this->assertIsString($html);
        $this->assertSame("<h1>Hello World!</h1>\n", $html);

        $this->verifyMockeryExpectations();
    }
}
