<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Hyde\Support\Models\Route;
use Hyde\Testing\TestsBladeViews;
use Hyde\Pages\DocumentationPage;
use Hyde\Testing\Support\TestView;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;
use Hyde\Framework\Features\Navigation\NavigationMenuGenerator;

/**
 * @see resources/views/components/docs/sidebar-items.blade.php
 */
class SidebarItemsViewTest extends TestCase
{
    use TestsBladeViews;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRoute();
    }

    protected function testView(): TestView
    {
        Hyde::routes()->addRoute(new Route(new DocumentationPage('foo')));
        Hyde::routes()->addRoute(new Route(new DocumentationPage('bar')));
        Hyde::routes()->addRoute(new Route(new DocumentationPage('baz')));

        return $this->view(view('hyde::components.docs.sidebar-items', [
            'sidebar' => NavigationMenuGenerator::handle(DocumentationSidebar::class),
        ]));
    }

    public function testComponentRenders()
    {
        $this->testView()->assertHasElement('#sidebar-items')->assertSeeTimes('listitem', 3);
    }

    public function testViewDoesNotContainActiveStateWhenNoPageIsActive()
    {
        $this->testView()
            ->assertDontSee('active')
            ->assertDontSee('Table of contents')
            ->assertDoesNotHaveAttribute('aria-current');
    }

    public function testViewContainsActiveStateWhenPageIsActive()
    {
        $this->mockCurrentPage('docs/foo');
        $this->mockPage(new DocumentationPage('foo'));

        $this->testView()
            ->assertSeeOnce('active')
            ->assertSeeOnce('Table of contents')
            ->assertHasAttribute('aria-current')
            ->assertAttributeIs('aria-current="true"');
    }

    public function testTypeAnnotationIsNotPresentInHtml()
    {
        $this->testView()->assertDontSee('@var')->assertDontSee('$group');
    }
}
