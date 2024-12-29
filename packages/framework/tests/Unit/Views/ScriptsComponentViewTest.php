<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * @see resources/views/layouts/scripts.blade.php
 */
class ScriptsComponentViewTest extends TestCase
{
    protected ?string $mockCurrentPage = null;

    protected function renderTestView(): string
    {
        config(['hyde.cache_busting' => false]);
        $this->mockCurrentPage($this->mockCurrentPage ?? '');

        return Blade::render(file_get_contents(
            Hyde::vendorPath('resources/views/layouts/scripts.blade.php')
        ));
    }

    public function testComponentCanBeRendered()
    {
        $this->assertStringContainsString('<script defer', $this->renderTestView());
    }

    public function testComponentHasLinkToAppJsFileWhenItExists()
    {
        Filesystem::touch('_media/app.js');
        $this->assertStringContainsString('<script defer src="media/app.js"', $this->renderTestView());
        Filesystem::unlink('_media/app.js');
    }

    public function testComponentDoesNotRenderLinkToAppJsWhenItDoesNotExist()
    {
        $this->assertStringNotContainsString('<script defer src="media/app.js"', $this->renderTestView());
    }

    public function testComponentUsesRelativePathToAppJsFileForNestedPages()
    {
        Filesystem::touch('_media/app.js');
        $this->mockCurrentPage = 'foo';
        $this->assertStringContainsString('<script defer src="media/app.js"', $this->renderTestView());
        $this->mockCurrentPage = 'foo/bar';
        $this->assertStringContainsString('<script defer src="../media/app.js"', $this->renderTestView());
        $this->mockCurrentPage = 'foo/bar/cat.html';
        $this->assertStringContainsString('<script defer src="../../media/app.js"', $this->renderTestView());
        $this->mockCurrentPage = null;
        Filesystem::unlink('_media/app.js');
    }

    public function testCanAddScriptsHtmlFromConfigHook()
    {
        config(['hyde.scripts' => '<script src="custom-hook.js"></script>']);

        $this->assertStringContainsString('<script src="custom-hook.js"></script>', $this->renderTestView());
    }

    public function testCanAddScriptsHtmlFromHtmlInclude()
    {
        $this->file('resources/includes/scripts.html', '<script src="html-include.js"></script>');

        $this->assertStringContainsString('<script src="html-include.js"></script>', $this->renderTestView());
    }

    public function testScriptsCanBePushedToTheComponentScriptsStack()
    {
        view()->share('routeKey', '');

        $this->assertStringContainsString('foo bar',
            Blade::render('
                @push("scripts")
                foo bar
                @endpush

                @include("hyde::layouts.scripts")'
            )
        );
    }
}
