<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Views\Components\BreadcrumbsComponent;
use Hyde\Pages\MarkdownPage;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\Route;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * @covers \Hyde\Framework\Views\Components\BreadcrumbsComponent
 *
 * @see \Hyde\Framework\Testing\Unit\BreadcrumbsComponentTest
 */
class BreadcrumbsComponentViewTest extends TestCase
{
    public function testRenderedBladeView()
    {
        Render::shouldReceive('getCurrentRoute')->once()->andReturn(new Route(new MarkdownPage('foo')));

        $html = Blade::renderComponent(new BreadcrumbsComponent());

        $expected = <<<'HTML'
            <nav aria-label="breadcrumb">
                <a href="/" class="hover:underline">Home</a>
                &nbsp;&gt;&gt;&nbsp;
                Foo
            </nav>
        HTML;

        $this->assertSame($this->stripIndentation($expected), $this->stripIndentation($html));
    }

    public function testRenderedBladeViewOnIndexPage()
    {
        Render::shouldReceive('getCurrentRoute')->once()->andReturn(new Route(new MarkdownPage('index')));

        $this->assertSame('', Blade::renderComponent(new BreadcrumbsComponent()));
    }

    protected function stripIndentation(string $string): string
    {
        return implode("\n", array_filter(array_map(fn($line) => ltrim($line), explode("\n", $string))));
    }
}
