<?php

/** @noinspection HtmlUnknownTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Contracts\View\View;
use Throwable;

/**
 * Very high level test of the sidebar views and their combinations of layouts.
 */
class SidebarViewTest extends TestCase
{
    protected string $html;

    protected function tearDown(): void
    {
        parent::setUp();

        unset($this->html);
    }

    public function testBaseSidebar()
    {
        $this->renderComponent(view('hyde::components.docs.sidebar'))
            ->assertSeeHtml('<nav id="sidebar-navigation"')
            ->assertSeeHtml('<a href="index.html">Back to home page</a>')
            ->assertSeeHtml('<ul id="sidebar-navigation-items" role="list" class="pl-2">')
            ->allGood();
    }

    protected function renderComponent(View $view): self
    {
        try {
            $this->html = $view->render();
            /** @noinspection LaravelFunctionsInspection */
            if (env('TEST_HTML_DEBUG', false)) {
                file_put_contents(Hyde::path('_site/test.html'), $this->html);
                echo "\e[0;32mCreated file: \e[0m".realpath(Hyde::path('_site/test.html'));
            }
        } catch (Throwable $exception) {
            /** @noinspection LaravelFunctionsInspection */
            if (env('TEST_HTML_DEBUG', false)) {
                throw $exception;
            }
            $this->fail($exception->getMessage());
        }

        $this->assertIsString($this->html);

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

    protected function allGood(): self
    {
        // Just an empty helper so we get easier Git diffs when adding new assertions.

        return $this;
    }
}
