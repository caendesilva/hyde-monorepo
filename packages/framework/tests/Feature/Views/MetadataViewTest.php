<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Views;

use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Hyde\Foundation\HydeKernel;

/**
 * This tests ensures all metadata is rendered correctly in the compiled pages.
 * Please see the MetadataBagTest class which tests the construction of the data,
 * as this test does not cover all configuration cases and possible formatting options.
 *
 * This test is useful both as a regression test, and also to ensure all tags are covered.
 *
 * @covers \Hyde\Framework\Features\Metadata\MetadataBag
 * @covers \Hyde\Framework\Features\Metadata\PageMetadataBag
 * @covers \Hyde\Framework\Features\Metadata\GlobalMetadataBag
 */
class MetadataViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withSiteUrl();
        config(['hyde.cache_busting' => false]);
    }

    protected function build(?string $page = null): void
    {
        if ($page) {
            $this->artisan("rebuild $page");
        } else {
            $this->artisan('build');
        }
    }

    protected function assertSee(string $page, string|array $text): string|array
    {
        if (is_array($text)) {
            foreach ($text as $string) {
                $this->assertSee($page, $string);
            }

            return $text;
        }

        $this->assertStringContainsString($text,
            file_get_contents(Hyde::path("_site/$page.html")),
            "Failed asserting that the page '$page' contains the text '$text'");

        return $text;
    }

    protected function assertAllTagsWereCovered(string $page, array $tags): void
    {
        $expected = file_get_contents(Hyde::path("_site/$page.html"));
        $actual = json_encode($tags);

        $this->assertEquals(
            substr_count($expected, '<meta'),
            substr_count($actual, '<meta'),
            "Failed asserting that all meta tags were covered in the page '$page'"
        );

        $this->assertEquals(
            substr_count($expected, '<link'),
            substr_count($actual, '<link'),
            "Failed asserting that all link tags were covered in the page '$page'"
        );
    }

    protected function getDefaultTags(): array
    {
        return [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<meta id="meta-color-scheme" name="color-scheme" content="light">',
            '<link rel="sitemap" href="https://example.com/sitemap.xml" type="application/xml" title="Sitemap">',
            '<meta name="generator" content="HydePHP v'.HydeKernel::VERSION.'">',
            '<meta property="og:site_name" content="HydePHP">',
        ];
    }

    public function testMetadataTagsInEmptyBladePage()
    {
        $this->file('_pages/test.blade.php', '@extends(\'hyde::layouts.app\')');
        $this->build('_pages/test.blade.php');

        $assertions = $this->assertSee('test', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - Test</title>',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<link rel="canonical" href="https://example.com/test.html">',
            '<meta name="twitter:title" content="HydePHP - Test">',
            '<meta property="og:title" content="HydePHP - Test">',
        ]));

        $this->assertAllTagsWereCovered('test', $assertions);
    }

    public function testMetadataTagsInEmptyMarkdownPage()
    {
        $this->markdown('_pages/test.md');
        $this->build('_pages/test.md');

        $assertions = $this->assertSee('test', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - Test</title>',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<link rel="canonical" href="https://example.com/test.html">',
            '<meta name="twitter:title" content="HydePHP - Test">',
            '<meta property="og:title" content="HydePHP - Test">',
        ]));

        $this->assertAllTagsWereCovered('test', $assertions);
    }

    public function testMetadataTagsInEmptyDocumentationPage()
    {
        $this->markdown('_docs/test.md');
        $this->build('_docs/test.md');

        $assertions = $this->assertSee('docs/test', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - Test</title>',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<link rel="canonical" href="https://example.com/docs/test.html">',
            '<meta name="twitter:title" content="HydePHP - Test">',
            '<meta property="og:title" content="HydePHP - Test">',
        ]));

        $this->assertAllTagsWereCovered('docs/test', $assertions);
    }

    public function testMetadataTagsInEmptyMarkdownPost()
    {
        $this->markdown('_posts/test.md');
        $this->build('_posts/test.md');

        $assertions = $this->assertSee('posts/test', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - Test</title>',
            '<link rel="alternate" href="https://example.com/feed.xml" type="application/rss+xml" title="HydePHP RSS Feed">',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<link rel="canonical" href="https://example.com/posts/test.html">',
            '<meta name="twitter:title" content="HydePHP - Test">',
            '<meta name="url" content="https://example.com/posts/test.html">',
            '<meta property="og:title" content="HydePHP - Test">',
            '<meta property="og:url" content="https://example.com/posts/test.html">',
            '<meta property="og:type" content="article">',
            '<meta itemprop="identifier" content="test">',
            '<meta itemprop="url" content="https://example.com/posts/test.html">',
        ]));

        $this->assertAllTagsWereCovered('posts/test', $assertions);
    }

    public function testMetadataTagsInMarkdownPostWithFlatFrontMatter()
    {
        // Run the test above, but with all front matter properties (without array notation)
        $this->file('_posts/test.md', <<<'MARKDOWN'
            ---
            title: "My title"
            description: "My description"
            category: "My category"
            date: "2022-01-01"
            author: "Mr. Hyde"
            image: image.jpg
            ---

            ## Hello World

            Lorem Ipsum Dolor Amet.
            MARKDOWN
        );
        $this->file('_media/image.jpg');
        $this->build('_posts/test.md');

        $assertions = $this->assertSee('posts/test', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - My title</title>',
            '<link rel="alternate" href="https://example.com/feed.xml" type="application/rss+xml" title="HydePHP RSS Feed">',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<link rel="canonical" href="https://example.com/posts/test.html">',
            '<meta name="twitter:title" content="HydePHP - My title">',
            '<meta name="description" content="My description">',
            '<meta name="author" content="Mr. Hyde">',
            '<meta name="keywords" content="My category">',
            '<meta name="url" content="https://example.com/posts/test.html">',
            '<meta property="og:title" content="HydePHP - My title">',
            '<meta property="og:description" content="My description">',
            '<meta property="og:url" content="https://example.com/posts/test.html">',
            '<meta property="og:type" content="article">',
            '<meta property="og:article:published_time" content="2022-01-01T00:00:00+00:00">',
            '<meta property="og:image" content="https://example.com/media/image.jpg">',
            '<meta itemprop="identifier" content="test">',
            '<meta itemprop="url" content="https://example.com/posts/test.html">',
            '<meta itemprop="url" content="https://example.com/media/image.jpg">',
            '<meta itemprop="contentUrl" content="https://example.com/media/image.jpg">',
        ]));

        $this->assertAllTagsWereCovered('posts/test', $assertions);
    }

    public function testCanonicalUrlTagsAreNotAddedWhenCanonicalUrlIsNotSet()
    {
        config(['hyde.url' => 'http://localhost']);

        $this->file('_posts/test.md', <<<'MARKDOWN'
            ---
            title: "My title"
            description: "My description"
            category: "My category"
            date: "2022-01-01"
            author: "Mr. Hyde"
            image: image.jpg
            ---

            ## Hello World

            Lorem Ipsum Dolor Amet.
            MARKDOWN
        );
        $this->file('_media/image.jpg');
        $this->build('_posts/test.md');

        $assertions = $this->assertSee('posts/test', [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<meta id="meta-color-scheme" name="color-scheme" content="light">',
            '<meta name="generator" content="HydePHP v'.HydeKernel::VERSION.'">',
            '<meta property="og:site_name" content="HydePHP">',
            '<title>HydePHP - My title</title>',
            '<link rel="stylesheet" href="../media/app.css">',
            '<meta name="twitter:title" content="HydePHP - My title">',
            '<meta name="description" content="My description">',
            '<meta name="author" content="Mr. Hyde">',
            '<meta name="keywords" content="My category">',
            '<meta property="og:title" content="HydePHP - My title">',
            '<meta property="og:description" content="My description">',
            '<meta property="og:type" content="article">',
            '<meta property="og:article:published_time" content="2022-01-01T00:00:00+00:00">',
            '<meta property="og:image" content="../media/image.jpg">',
            '<meta itemprop="identifier" content="test">',
            '<meta itemprop="url" content="../media/image.jpg">',
            '<meta itemprop="contentUrl" content="../media/image.jpg">',

            // '<link rel="sitemap" href="http://localhost/sitemap.xml" type="application/xml" title="Sitemap">',
            // '<link rel="alternate" href="http://localhost/feed.xml" type="application/rss+xml" title="HydePHP RSS Feed">',
            // '<link rel="canonical" href="http://localhost/posts/test.html">',
            // '<meta name="url" content="http://localhost/posts/test.html">',
            // '<meta property="og:url" content="http://localhost/posts/test.html">',
            // '<meta itemprop="url" content="http://localhost/posts/test.html">',
        ]);

        $this->assertAllTagsWereCovered('posts/test', $assertions);

        $dontSee = [
            '<link rel="sitemap"',
            '<link rel="alternate"',
            '<link rel="canonical"',
            '<meta name="url"',
            '<meta property="og:url"',
            '<meta itemprop="url" content="http',
        ];

        $contents = file_get_contents(Hyde::path('_site/posts/test.html'));

        foreach ($dontSee as $text) {
            $this->assertStringNotContainsString($text, $contents);
        }
    }

    public function testMetadataTagsInMarkdownPageWithDescription()
    {
        $this->file('_pages/test-page.md', <<<'MARKDOWN'
            ---
            title: "My Page Title"
            description: "My page description"
            ---

            ## Welcome to My Page

            This is a test page with a description.
            MARKDOWN
        );
        $this->build('_pages/test-page.md');

        $this->assertSee('test-page', array_merge($this->getDefaultTags(), [
            '<title>HydePHP - My Page Title</title>',
            '<link rel="stylesheet" href="https://example.com/media/app.css">',
            '<meta name="twitter:title" content="HydePHP - My Page Title">',
            '<meta property="og:title" content="HydePHP - My Page Title">',
            '<meta property="og:description" content="My page description">',
            '<meta name="description" content="My page description">',
        ]));
    }
}
