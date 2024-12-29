<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Views;

use Hyde\Hyde;
use Hyde\Facades\Features;
use Hyde\Testing\TestCase;
use Hyde\Testing\TestsBladeViews;
use Hyde\Pages\DocumentationPage;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

class SidebarBrandViewTest extends TestCase
{
    use TestsBladeViews;

    public function testSidebarBrandView()
    {
        $view = $this->view(view('hyde::components.docs.sidebar-brand'));

        $view->assertSee('HydePHP Docs');
        $view->assertSee('theme-toggle-button');
        $view->assertDontSee('href');
    }

    public function testSidebarBrandViewWithHomeRoute()
    {
        Hyde::routes()->addRoute((new DocumentationPage('index'))->getRoute());

        $view = $this->view(view('hyde::components.docs.sidebar-brand'));

        $view->assertSee('HydePHP Docs');
        $view->assertSee('theme-toggle-button');
        $view->assertSeeHtml('<a href="docs/index.html">HydePHP Docs</a>', true);
    }

    public function testSidebarBrandViewWithDefaultHeaderText()
    {
        config(['docs.sidebar' => []]);

        $view = $this->view(view('hyde::components.docs.sidebar-brand'));

        $view->assertSee('Documentation');
        $view->assertDontSee('HydePHP Docs');
    }

    public function testSidebarBrandViewWithDefaultHeaderTextAndHomeRoute()
    {
        Hyde::routes()->addRoute((new DocumentationPage('index'))->getRoute());

        config(['docs.sidebar' => []]);

        $view = $this->view(view('hyde::components.docs.sidebar-brand'));

        $view->assertSee('Documentation');
        $view->assertSeeHtml('<a href="docs/index.html">Documentation</a>', true);
        $view->assertDontSee('HydePHP Docs');
    }

    public function testSidebarBrandViewWithoutDarkmodeFeature()
    {
        Features::mock('darkmode', false);

        $view = $this->view(view('hyde::components.docs.sidebar-brand'));

        $view->assertSee('HydePHP Docs');
        $view->assertDontSee('theme-toggle-button');
    }

    protected function testViewData(): array
    {
        return [
            'sidebar' => new DocumentationSidebar(),
        ];
    }
}
