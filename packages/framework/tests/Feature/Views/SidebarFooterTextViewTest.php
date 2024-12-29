<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Views;

use Hyde\Facades\Config;
use Hyde\Testing\TestCase;
use Hyde\Testing\TestsBladeViews;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

class SidebarFooterTextViewTest extends TestCase
{
    use TestsBladeViews;

    public function testSidebarFooterTextViewWithDefaultConfig()
    {
        $view = $this->view(view('hyde::components.docs.sidebar-footer-text', $this->withSidebar()));

        $view->assertSeeHtml('<a href="../">Back to home page</a>');
    }

    public function testSidebarFooterTextViewWhenConfigOptionIsMarkdownString()
    {
        Config::set('docs.sidebar.footer', 'Your Markdown String Here');

        $view = $this->view(view('hyde::components.docs.sidebar-footer-text', $this->withSidebar()));

        $view->assertSeeText('Your Markdown String Here');
    }

    protected function withSidebar(): array
    {
        return ['sidebar' => new DocumentationSidebar()];
    }
}
