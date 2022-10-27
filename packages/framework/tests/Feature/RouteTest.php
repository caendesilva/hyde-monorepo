<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Exceptions\BaseUrlNotSetException;
use Hyde\Hyde;
use Hyde\Pages\MarkdownPage;
use Hyde\Routing\Route;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Routing\Route
 */
class RouteTest extends TestCase
{
    public function test_constructor_creates_route_from_page_model()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_get_page_type_returns_fully_qualified_class_name()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertEquals(MarkdownPage::class, $route->getPageType());
    }

    public function test_get_source_model_returns_page_model()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertInstanceOf(MarkdownPage::class, $route->getSourceModel());
        $this->assertSame($page, $route->getSourceModel());
    }

    public function test_get_route_key_returns_page_path()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertEquals($page->getRouteKey(), $route->getRouteKey());
    }

    public function test_get_source_file_path_returns_page_source_path()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertEquals($page->getSourcePath(), $route->getSourcePath());
    }

    public function test_get_output_file_path_returns_page_output_path()
    {
        $page = new MarkdownPage();
        $route = new Route($page);

        $this->assertEquals($page->getOutputPath(), $route->getOutputPath());
    }

    public function test_get_link_returns_correct_path_for_root_pages()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $this->assertEquals(Hyde::relativeLink($route->getOutputPath()), $route->getLink());
        $this->assertEquals('foo.html', $route->getLink());
    }

    public function test_get_link_returns_correct_path_for_nested_pages()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo/bar'));
        $this->assertEquals(Hyde::relativeLink($route->getOutputPath()), $route->getLink());
        $this->assertEquals('foo/bar.html', $route->getLink());
    }

    public function test_get_link_returns_correct_path_for_nested_current_page()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        view()->share('currentPage', 'foo/bar');
        $this->assertEquals(Hyde::relativeLink($route->getOutputPath()), $route->getLink());
        $this->assertEquals('../foo.html', $route->getLink());
    }

    public function test_get_link_returns_pretty_url_if_enabled()
    {
        config(['site.pretty_urls' => true]);
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $this->assertEquals(Hyde::relativeLink($route->getOutputPath()), $route->getLink());
        $this->assertEquals('foo', $route->getLink());
    }

    public function test_to_string_is_alias_for_get_link()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $this->assertEquals($route->getLink(), (string) $route);
    }

    public function test_get_qualified_url_returns_hyde_url_for_output_file_path()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $this->assertEquals(Hyde::url('foo.html'), $route->getQualifiedUrl());
    }

    public function test_get_qualified_url_returns_hyde_url_for_nested_pages()
    {
        $route = new Route(new MarkdownPage(identifier: 'foo/bar'));
        $this->assertEquals(Hyde::url('foo/bar.html'), $route->getQualifiedUrl());
    }

    public function test_get_qualified_url_returns_pretty_url_if_enabled()
    {
        config(['site.pretty_urls' => true]);
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $this->assertEquals(Hyde::url('foo'), $route->getQualifiedUrl());
    }

    public function test_get_qualified_url_throws_exception_when_a_base_url_is_not_set()
    {
        config(['site.url' => null]);
        $this->expectException(BaseUrlNotSetException::class);
        $route = new Route(new MarkdownPage(identifier: 'foo'));
        $route->getQualifiedUrl();
    }

    public function test_to_array_method()
    {
        $this->assertEquals([
            'routeKey' => 'foo',
            'sourcePath' => '_pages/foo.md',
            'outputPath' => 'foo.html',
            'sourceModel' => MarkdownPage::class,
        ], (new MarkdownPage('foo'))->getRoute()->toArray());
    }

    public function testIsWithRoute()
    {
        $route = new Route(new MarkdownPage('foo'));
        $this->assertTrue($route->is($route));

        $route2 = new Route(new MarkdownPage('bar'));
        $this->assertFalse($route->is($route2));
    }

    public function testIsWithRouteKey()
    {
        $route = new Route(new MarkdownPage('foo'));
        $this->assertTrue($route->is('foo'));
        $this->assertFalse($route->is('bar'));
    }
}
