<?php

/** @noinspection HtmlUnknownTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Features\Navigation\DocumentationSidebar;
use Hyde\Testing\TestCase;
use Illuminate\Contracts\View\View;
use Hyde\Framework\Features\Navigation\NavigationMenuGenerator;

/**
 * Very high level test of the sidebar views and their combinations of layouts.
 *
 * It should cover all possible rendering paths, so while not all code is asserted on,
 * all views should be rendered at least once, and thus we know they can at least be rendered.
 */
class SidebarViewTest extends TestCase
{
    protected string $html;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->html);
    }

    public function testBaseSidebar()
    {
        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeText('HydePHP Docs')
            ->assertSeeHtml('<ul id="sidebar-items" role="list"')
            ->assertSeeHtml('<nav id="sidebar-navigation"')
            ->assertSeeHtml('<footer id="sidebar-footer"')
            ->assertSeeHtml('<a href="../">Back to home page</a>')
            ->assertSeeHtml('<span class="sr-only">Toggle dark theme</span>')
            ->assertDontSee('<a href="docs/index.html">')
            ->assertDontSee('<li class="sidebar-item');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-items'));

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-brand'));
        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-footer-text'));
    }

    public function testBaseSidebarWithoutFooter()
    {
        config(['docs.sidebar.footer' => false]);

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertDontSee('<footer id="sidebar-footer"')
            ->assertDontSee('Back to home page');
    }

    public function testBaseSidebarWithCustomFooterText()
    {
        config(['docs.sidebar.footer' => 'My **Markdown** Footer Text']);

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeHtml('<footer id="sidebar-footer"')
            ->assertSeeHtml('<p>My <strong>Markdown</strong> Footer Text</p>')
            ->assertDontSee('Back to home page');
    }

    public function testBaseSidebarCustomHeaderBrand()
    {
        config(['docs.sidebar.header' => 'My Custom Header']);

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeText('My Custom Header')
            ->assertDontSee('HydePHP Docs');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-brand'));
    }

    public function testBaseSidebarWithItems()
    {
        $this->mockRoute();
        $this->file('_docs/index.md');
        $this->file('_docs/first.md');

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeHtml('<a href="docs/index.html">')
            ->assertSeeHtml('<nav id="sidebar-navigation"')
            ->assertSeeHtml('<ul id="sidebar-items" role="list" class="pl-2">')
            ->assertSeeHtml('<li class="sidebar-item');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-items'));
    }

    public function testSidebarWithGroupedItems()
    {
        $this->mockRoute();
        $this->mockPage();
        $this->file('_docs/index.md');
        $this->markdown('_docs/first.md', matter: ['navigation.group' => 'Group 1']);

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeText('Group 1')
            ->assertSeeText('First')
            ->assertSeeHtml('href="docs/first.html"')
            ->assertSeeHtml('<ul id="sidebar-items" role="list"')
            ->assertSeeHtml('<li class="sidebar-item')
            ->assertSeeHtml('<li class="sidebar-group')
            ->assertSeeHtml('class="sidebar-group"')
            ->assertSeeHtml('class="sidebar-group-header')
            ->assertSeeHtml('class="sidebar-group-heading')
            ->assertSeeHtml('class="sidebar-group-toggle')
            ->assertSeeHtml('class="sidebar-group-toggle-icon')
            ->assertSeeHtml('class="sidebar-group-items')
            ->assertSee('groupOpen');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-items', [
            'grouped' => true,
        ]));

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-group-toggle-button'));
    }

    public function testSidebarWithNonCollapsibleGroupedItems()
    {
        $this->mockRoute();
        $this->mockPage();
        $this->file('_docs/index.md');
        $this->markdown('_docs/first.md', matter: ['navigation.group' => 'Group 1']);
        config(['docs.sidebar.collapsible' => false]);

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeText('Group 1')
            ->assertSeeText('First')
            ->assertSeeHtml('href="docs/first.html"')
            ->assertSeeHtml('<ul id="sidebar-items" role="list"')
            ->assertSeeHtml('<li class="sidebar-item')
            ->assertSeeHtml('<li class="sidebar-group')
            ->assertSeeHtml('class="sidebar-group"')
            ->assertSeeHtml('class="sidebar-group-header')
            ->assertSeeHtml('class="sidebar-group-heading')
            ->assertSeeHtml('class="sidebar-group-items')
            ->assertDontSee('sidebar-group-toggle')
            ->assertDontSee('sidebar-group-toggle-icon')
            ->assertDontSee('groupOpen');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-items', [
            'grouped' => true,
        ]));

        $this->assertViewWasNotRendered(view('hyde::components.docs.sidebar-group-toggle-button'));
    }

    public function testSidebarWithMixedGroupedAndUngroupedItems()
    {
        $this->mockRoute();
        $this->mockPage();
        $this->file('_docs/index.md');
        $this->markdown('_docs/first.md', matter: ['navigation.group' => 'Group 1']);
        $this->markdown('_docs/second.md');

        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeText('Group 1')
            ->assertSeeText('First')
            ->assertSeeText('Other')
            ->assertSeeText('Second')
            ->assertSeeHtml('href="docs/first.html"')
            ->assertSeeHtml('href="docs/second.html"');

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-items', [
            'grouped' => true,
        ]));

        $this->assertViewWasRendered(view('hyde::components.docs.sidebar-group-toggle-button'));
    }

    protected function renderComponent(View $view): self
    {
        $sidebar = NavigationMenuGenerator::handle(DocumentationSidebar::class);
        app()->instance('navigation.sidebar', $sidebar);
        view()->share('sidebar', $sidebar);

        $this->html = $view->render();
        $this->assertIsString($this->html);

        return $this;
    }

    protected function assertViewWasRendered(View $view): self
    {
        $this->assertStringContainsString($view->render(), $this->html);

        return $this;
    }

    protected function assertViewWasNotRendered(View $view): self
    {
        $this->assertStringNotContainsString($view->render(), $this->html);

        return $this;
    }

    protected function assertSee(string $text, bool $escape = true): self
    {
        $this->assertStringContainsString($escape ? e($text) : $text, $this->html);

        return $this;
    }

    protected function assertSeeHtml(string $text, bool $escape = false): self
    {
        $this->assertStringContainsString($escape ? e($text) : $text, $this->html);

        return $this;
    }

    protected function assertSeeText(string $text): self
    {
        $this->assertSee($text);

        return $this;
    }

    protected function assertDontSee(string $text): self
    {
        $this->assertStringNotContainsString($text, $this->html);

        return $this;
    }
}
