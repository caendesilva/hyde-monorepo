<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Facades\HydeFront;
use Hyde\Hyde;
use Hyde\Support\Facades\Render;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * @see resources/views/layouts/styles.blade.php
 */
class StylesComponentViewTest extends TestCase
{
    protected ?string $mockCurrentPage = null;

    protected function renderTestView(): string
    {
        config(['hyde.cache_busting' => false]);
        $this->mockCurrentPage($this->mockCurrentPage ?? '');

        return Blade::render(file_get_contents(
            Hyde::vendorPath('resources/views/layouts/styles.blade.php')
        ));
    }

    public function testComponentCanBeRendered()
    {
        $this->assertStringContainsString('<link rel="stylesheet"', $this->renderTestView());
    }

    public function testComponentHasLinkToAppCssFile()
    {
        $this->assertStringContainsString('<link rel="stylesheet" href="media/app.css"', $this->renderTestView());
    }

    public function testComponentUsesRelativePathToAppCssFileForNestedPages()
    {
        $this->mockCurrentPage = 'foo';
        $this->assertStringContainsString('<link rel="stylesheet" href="media/app.css"', $this->renderTestView());
        $this->mockCurrentPage = 'foo/bar';
        $this->assertStringContainsString('<link rel="stylesheet" href="../media/app.css"', $this->renderTestView());
        $this->mockCurrentPage = 'foo/bar/cat.html';
        $this->assertStringContainsString('<link rel="stylesheet" href="../../media/app.css"', $this->renderTestView());
        $this->mockCurrentPage = null;
    }

    public function testComponentDoesNotRenderLinkToAppCssWhenItDoesNotExist()
    {
        rename(Hyde::path('_media/app.css'), Hyde::path('_media/app.css.bak'));
        $this->assertStringNotContainsString('<link rel="stylesheet" href="media/app.css"', $this->renderTestView());
        rename(Hyde::path('_media/app.css.bak'), Hyde::path('_media/app.css'));
    }

    public function testStylesCanBePushedToTheComponentStylesStack()
    {
        Render::share('routeKey', '');

        $this->assertStringContainsString('foo bar',
            Blade::render('
                @push("styles")
                foo bar
                @endpush

                @include("hyde::layouts.styles")'
            )
        );
    }

    public function testComponentRendersTailwindPlayCdnLinkWhenEnabledInConfig()
    {
        config(['hyde.use_play_cdn' => true]);
        $this->assertStringContainsString('<script src="https://cdn.tailwindcss.com?plugins=typography"></script>', $this->renderTestView());
    }

    public function testComponentRendersAppCdnLinkWhenEnabledInConfig()
    {
        config(['hyde.load_app_styles_from_cdn' => true]);
        $this->assertStringContainsString(HydeFront::cdnLink('app.css'), $this->renderTestView());
    }

    public function testComponentDoesNotRenderLinkToLocalAppCssWhenCdnLinkIsEnabledInConfig()
    {
        config(['hyde.load_app_styles_from_cdn' => true]);
        $this->assertStringNotContainsString('<link rel="stylesheet" href="media/app.css"', $this->renderTestView());
    }
}
