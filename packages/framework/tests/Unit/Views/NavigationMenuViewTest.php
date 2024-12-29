<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Illuminate\Support\Str;
use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Hyde\Pages\MarkdownPage;

/**
 * @see resources/views/layouts/navigation.blade.php
 */
class NavigationMenuViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRoute();
        $this->mockPage();

        Hyde::boot();
    }

    protected function render(): string
    {
        return view('hyde::layouts.navigation')->render();
    }

    public function testComponentCanBeRendered()
    {
        $this->assertStringContainsString('id="main-navigation"', $this->render());
    }

    public function testComponentContainsDarkModeButton()
    {
        $this->assertStringContainsString('theme-toggle-button', $this->render());
    }

    public function testComponentContainsNavigationMenuToggleButton()
    {
        $this->assertStringContainsString('id="navigation-toggle-button"', $this->render());
    }

    public function testComponentContainsMainNavigationLinks()
    {
        $this->assertStringContainsString('id="main-navigation-links"', $this->render());
    }

    public function testComponentContainsIndexHtmlLink()
    {
        $this->assertStringContainsString('href="index.html"', $this->render());
    }

    public function testComponentNotContains404HtmlLink()
    {
        $this->assertStringNotContainsString('href="404.html"', $this->render());
    }

    public function testNavigationMenuWithRootPages()
    {
        $foo = new MarkdownPage('foo');
        $bar = new MarkdownPage('bar');

        Hyde::routes()->add($foo->getRoute());
        Hyde::routes()->add($bar->getRoute());

        $this->mockRoute($foo->getRoute());
        $this->mockPage($foo);

        $contents = $foo->compile();

        $this->assertStringContainsString('<a href="foo.html" aria-current="page" class="', $contents);
        $this->assertStringContainsString('<a href="bar.html" class="', $contents);
    }

    public function testNavigationMenuWithDropdownPages()
    {
        config(['hyde.navigation.subdirectory_display' => 'dropdown']);

        $page = new MarkdownPage('page');
        $bar = new MarkdownPage('foo/bar');
        $baz = new MarkdownPage('foo/baz');

        Hyde::routes()->add($page->getRoute());
        Hyde::routes()->add($bar->getRoute());
        Hyde::routes()->add($baz->getRoute());

        $this->mockRoute($page->getRoute());
        $this->mockPage($page);

        $contents = $page->compile();

        $this->assertStringContainsString('dropdown-container', $contents);
        $this->assertStringContainsString('dropdown-button', $contents);

        $dropdown = Str::between($contents, '<ul class="dropdown-items', '</ul>');

        $this->assertStringContainsString('<a href="foo/bar.html"', $dropdown);
        $this->assertStringContainsString('<a href="foo/baz.html"', $dropdown);
    }

    public function testNavigationMenuWithDropdownPagesWithRootGroupPage()
    {
        config(['hyde.navigation.subdirectory_display' => 'dropdown']);

        $foo = new MarkdownPage('foo');
        $bar = new MarkdownPage('foo/bar');
        $baz = new MarkdownPage('foo/baz');

        Hyde::routes()->add($foo->getRoute());
        Hyde::routes()->add($bar->getRoute());
        Hyde::routes()->add($baz->getRoute());

        $this->mockRoute($foo->getRoute());
        $this->mockPage($foo);

        $contents = $foo->compile();

        $this->assertStringContainsString('dropdown-container', $contents);
        $this->assertStringContainsString('dropdown-button', $contents);

        $dropdown = Str::between($contents, '<ul class="dropdown-items', '</ul>');

        $this->assertStringContainsString('<a href="foo/bar.html"', $dropdown);
        $this->assertStringContainsString('<a href="foo/baz.html"', $dropdown);
    }

    public function testNavigationMenuLabelCanBeChangedInFrontMatter()
    {
        $this->file('_pages/foo.md', '---
navigation:
  label: "My custom label"
---
');
        Hyde::boot();

        $this->artisan('rebuild _pages/foo.md');
        $this->assertStringContainsString('My custom label', file_get_contents(Hyde::path('_site/foo.html')));
        Filesystem::unlink('_site/foo.html');
    }

    public function testNavigationMenuLabelCanBeChangedInBladeMatter()
    {
        $this->file('_pages/foo.blade.php', <<<'BLADE'
@extends('hyde::layouts.app')
@php($navigation = ['label' => 'My custom label'])
BLADE
        );
        Hyde::boot();

        $this->artisan('rebuild _pages/foo.blade.php');
        $this->assertStringContainsString('My custom label', file_get_contents(Hyde::path('_site/foo.html')));
        Filesystem::unlink('_site/foo.html');
    }
}
