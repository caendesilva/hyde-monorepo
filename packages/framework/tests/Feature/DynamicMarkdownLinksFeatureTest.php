<?php

/** @noinspection HtmlUnknownTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Hyde\Support\Includes;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\Models\Route;
use Hyde\Pages\DocumentationPage;
use Hyde\Foundation\Facades\Routes;
use Hyde\Markdown\Processing\DynamicMarkdownLinkProcessor;

/**
 * @covers \Hyde\Markdown\Processing\DynamicMarkdownLinkProcessor
 * @covers \Hyde\Framework\Concerns\Internal\SetsUpMarkdownConverter
 *
 * @see \Hyde\Framework\Testing\Feature\Services\Markdown\DynamicMarkdownLinkProcessorTest
 */
class DynamicMarkdownLinksFeatureTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        touch('_media/logo.png');
        touch('_media/image.jpg');

        DynamicMarkdownLinkProcessor::resetAssetMapCache();
    }

    public static function tearDownAfterClass(): void
    {
        unlink('_media/logo.png');
        unlink('_media/image.jpg');

        DynamicMarkdownLinkProcessor::resetAssetMapCache();

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['hyde.cache_busting' => false]);
    }

    public function testBasicDynamicMarkdownLinks()
    {
        $input = <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        ![Logo](/_media/logo.png)
        MARKDOWN;

        $expected = <<<'HTML'
        <p><a href="index.html">Home</a>
        <img src="media/logo.png" alt="Logo" /></p>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testDynamicMarkdownLinksInParagraphs()
    {
        $input = <<<'MARKDOWN'
        This is a paragraph with a [link to home](/_pages/index.blade.php).

        Another paragraph with an ![image](/_media/image.jpg).
        MARKDOWN;

        $expected = <<<'HTML'
        <p>This is a paragraph with a <a href="index.html">link to home</a>.</p>
        <p>Another paragraph with an <img src="media/image.jpg" alt="image" />.</p>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testDynamicMarkdownLinksInLists()
    {
        $input = <<<'MARKDOWN'
        - [Home](/_pages/index.blade.php)
        - ![Logo](/_media/logo.png)
        MARKDOWN;

        $expected = <<<'HTML'
        <ul>
        <li>
        <a href="index.html">Home</a>
        </li>
        <li>
        <img src="media/logo.png" alt="Logo" />
        </li>
        </ul>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testDynamicMarkdownLinksWithNestedRoutes()
    {
        Routes::addRoute(new Route(new MarkdownPage('about/contact')));

        $input = <<<'MARKDOWN'
        [Contact](/_pages/about/contact.md)
        MARKDOWN;

        $expected = <<<'HTML'
        <p><a href="about/contact.html">Contact</a></p>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testMixOfDynamicAndRegularMarkdownLinks()
    {
        $input = <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        [External](https://example.com)
        [Regular](regular-link.html)
        ![Logo](/_media/logo.png)
        ![External Image](https://example.com/image.jpg)
        MARKDOWN;

        $expected = <<<'HTML'
        <p><a href="index.html">Home</a>
        <a href="https://example.com">External</a>
        <a href="regular-link.html">Regular</a>
        <img src="media/logo.png" alt="Logo" />
        <img src="https://example.com/image.jpg" alt="External Image" /></p>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testLinksWithLeadingSlash()
    {
        $input = <<<'MARKDOWN'
        [Home with slash](/_pages/index.blade.php)
        ![Logo with slash](/_media/logo.png)
        MARKDOWN;

        $expected = <<<'HTML'
        <p><a href="index.html">Home with slash</a>
        <img src="media/logo.png" alt="Logo with slash" /></p>

        HTML;

        $this->assertSame($expected, Hyde::markdown($input)->toHtml());
    }

    public function testCanRenderPageWithDynamicMarkdownLinks()
    {
        $this->file('_pages/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        ![Logo](/_media/logo.png)

        [non-existent](/_pages/non-existent.md)
        ![non-existent](/_media/non-existent.png)
        MARKDOWN);

        $page = MarkdownPage::get('test');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="index.html">Home</a>',
            '<img src="media/logo.png" alt="Logo" />',
            '<a href="/_pages/non-existent.md">non-existent</a>',
            '<img src="/_media/non-existent.png" alt="non-existent" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testCanRenderNestedPageWithDynamicMarkdownLinks()
    {
        $this->file('_pages/nested/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        ![Logo](/_media/logo.png)

        [non-existent](/_pages/non-existent.md)
        ![non-existent](/_media/non-existent.png)
        MARKDOWN);

        $page = MarkdownPage::get('nested/test');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="../index.html">Home</a>',
            '<img src="../media/logo.png" alt="Logo" />',
            '<a href="/_pages/non-existent.md">non-existent</a>',
            '<img src="/_media/non-existent.png" alt="non-existent" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testCanRenderIncludeWithDynamicMarkdownLinks()
    {
        // if there is no current path data, we assume the file is included from the root

        $this->file('resources/includes/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        ![Logo](/_media/logo.png)

        [non-existent](/_pages/non-existent.md)
        ![non-existent](/_media/non-existent.png)
        MARKDOWN);

        $html = Includes::markdown('test')->toHtml();

        $expected = [
            '<a href="index.html">Home</a>',
            '<img src="media/logo.png" alt="Logo" />',
            '<a href="/_pages/non-existent.md">non-existent</a>',
            '<img src="/_media/non-existent.png" alt="non-existent" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testCanRenderIncludeFromNestedPageWithDynamicMarkdownLinks()
    {
        $this->mockCurrentPage('nested/test');

        $this->file('resources/includes/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        ![Logo](/_media/logo.png)

        [non-existent](/_pages/non-existent.md)
        ![non-existent](/_media/non-existent.png)
        MARKDOWN);

        $html = Includes::markdown('test')->toHtml();

        $expected = [
            '<a href="../index.html">Home</a>',
            '<img src="../media/logo.png" alt="Logo" />',
            '<a href="/_pages/non-existent.md">non-existent</a>',
            '<img src="/_media/non-existent.png" alt="non-existent" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testDynamicMarkdownLinksForDifferentPageTypes()
    {
        $this->file('_pages/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        [Documentation](/_docs/index.md)
        [Readme](/_docs/readme.md)
        [Example](/_pages/example.md)
        [Contact](/_pages/about/contact.md)
        [About](/_pages/about/index.md)
        [Post](/_posts/hello-world.md)
        
        ![Logo](/_media/logo.png)
        MARKDOWN);

        Routes::addRoute(new Route(new MarkdownPage('example')));
        Routes::addRoute(new Route(new MarkdownPage('about/index')));
        Routes::addRoute(new Route(new MarkdownPage('about/contact')));
        Routes::addRoute(new Route(new MarkdownPost('hello-world')));
        Routes::addRoute(new Route(new DocumentationPage('index')));
        Routes::addRoute(new Route(new DocumentationPage('readme')));

        $page = MarkdownPage::get('test');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="index.html">Home</a>',
            '<a href="docs/index.html">Documentation</a>',
            '<a href="docs/readme.html">Readme</a>',
            '<a href="example.html">Example</a>',
            '<a href="about/contact.html">Contact</a>',
            '<a href="about/index.html">About</a>',
            '<a href="posts/hello-world.html">Post</a>',
            '<img src="media/logo.png" alt="Logo" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testDynamicMarkdownLinksUseTheSitePrettyUrlConfiguration()
    {
        config(['hyde.pretty_urls' => true]);

        $this->file('_pages/test.md', <<<'MARKDOWN'
        [Home](/_pages/index.blade.php)
        [Documentation](/_docs/index.md)
        [Readme](/_docs/readme.md)
        [Example](/_pages/example.md)
        [Contact](/_pages/about/contact.md)
        [About](/_pages/about/index.md)
        [Post](/_posts/hello-world.md)
        
        ![Logo](/_media/logo.png)
        MARKDOWN);

        Routes::addRoute(new Route(new MarkdownPage('example')));
        Routes::addRoute(new Route(new MarkdownPage('about/index')));
        Routes::addRoute(new Route(new MarkdownPage('about/contact')));
        Routes::addRoute(new Route(new MarkdownPost('hello-world')));
        Routes::addRoute(new Route(new DocumentationPage('index')));
        Routes::addRoute(new Route(new DocumentationPage('readme')));

        $page = MarkdownPage::get('test');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="./">Home</a>',
            '<a href="docs/">Documentation</a>',
            '<a href="docs/readme">Readme</a>',
            '<a href="example">Example</a>',
            '<a href="about/contact">Contact</a>',
            '<a href="about/">About</a>',
            '<a href="posts/hello-world">Post</a>',
            '<img src="media/logo.png" alt="Logo" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }

    public function testDynamicMarkdownLinksFromDifferentPageTypes()
    {
        $links = <<<'MARKDOWN'
        [Docs](/_docs/docs.md)
        [Post](/_posts/post.md)
        [Page](/_pages/page.md)
        ![Logo](/_media/logo.png)
        MARKDOWN;

        $this->file('_docs/docs.md', $links);
        $this->file('_posts/post.md', $links);
        $this->file('_pages/page.md', $links);

        $page = DocumentationPage::get('docs');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="../docs/docs.html">Docs</a>',
            '<a href="../posts/post.html">Post</a>',
            '<a href="../page.html">Page</a>',
            '<img src="../media/logo.png" alt="Logo" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }

        $page = MarkdownPost::get('post');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="../docs/docs.html">Docs</a>',
            '<a href="../posts/post.html">Post</a>',
            '<a href="../page.html">Page</a>',
            '<img src="../media/logo.png" alt="Logo" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }

        $page = MarkdownPage::get('page');
        Hyde::shareViewData($page);
        $html = $page->compile();

        $expected = [
            '<a href="docs/docs.html">Docs</a>',
            '<a href="posts/post.html">Post</a>',
            '<a href="page.html">Page</a>',
            '<img src="media/logo.png" alt="Logo" />',
        ];

        foreach ($expected as $expectation) {
            $this->assertStringContainsString($expectation, $html);
        }
    }
}
