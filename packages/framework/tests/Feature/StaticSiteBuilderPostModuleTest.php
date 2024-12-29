<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Hyde;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * Test the post compiler module.
 *
 * @see \Hyde\Framework\Testing\Unit\Pages\MarkdownPostParserTest for the Markdown parser test.
 */
class StaticSiteBuilderPostModuleTest extends TestCase
{
    protected MarkdownPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file('_media/image.png');

        $this->post = MarkdownPost::make('test-post', [
            'title' => 'Adventures in Wonderland',
            'description' => 'All in the golden afternoon, full leisurely we glide.',
            'category' => 'novels',
            'author' => 'Lewis Carroll',
            'date' => '1865-11-18 18:52',
            'image' => 'image.png',
        ], <<<'MARKDOWN'
            ## CHAPTER I. DOWN THE RABBIT-HOLE.

            So she was considering in her own mind, as well as she could, for the hot day made her feel very sleepy and stupid.
            MARKDOWN
        );
    }

    protected function tearDown(): void
    {
        Filesystem::unlink('_site/posts/test-post.html');

        parent::tearDown();
    }

    public function testCanCreatePost()
    {
        StaticPageBuilder::handle($this->post);

        $this->assertFileExists(Hyde::path('_site/posts/test-post.html'));
    }

    public function testPostContainsExpectedContent()
    {
        $this->inspectHtml([
            'Adventures in Wonderland',
            'Saturday Nov 18th, 1865, at 6:52pm',
            'Lewis Carroll',
            'in the category "novels"',
            '<h2>CHAPTER I. DOWN THE RABBIT-HOLE.</h2>',
            '<p>So she was considering in her own mind, as well as she could',
        ]);
    }

    public function testPostContainsExpectedElements()
    {
        $this->inspectHtml([
            '<!DOCTYPE html>',
            '<html',
            '<head',
            '<body',
            '<main',
            '<article',
            '<meta',
            '<header',
            '<h1',
            '<time',
            '<address',
        ]);
    }

    public function testPostContainsExpectedMetaTags()
    {
        $this->inspectHtml([
            '<meta name="description" content="All in the golden afternoon, full leisurely we glide.">',
            '<meta name="author" content="Lewis Carroll">',
            '<meta name="keywords" content="novels">',
            '<meta property="og:type" content="article">',
            '<meta property="og:title" content="HydePHP - Adventures in Wonderland">',
            '<meta property="og:article:published_time" content="1865-11-18T18:52:00+00:00">',
        ]);
    }

    public function testPostContainsExpectedItemprops()
    {
        $this->inspectHtml([
            'itemtype="https://schema.org/BlogPosting"',
            'itemtype="https://schema.org/Person"',
            'itemprop="identifier"',
            'itemprop="headline"',
            'itemprop="dateCreated datePublished"',
            'itemprop="author"',
            'itemprop="name"',
            'itemprop="articleBody"',
        ]);
    }

    public function testPostContainsExpectedAriaSupport()
    {
        $this->inspectHtml([
            'role="doc-pageheader"',
            'role="doc-introduction"',
            'aria-label="About the post"',
        ]);
    }

    public function testPostImageIsResolvedRelatively()
    {
        $this->inspectHtml([
            '<meta property="og:image" content="../media/image.png?v=00000000">',
            '<meta itemprop="url" content="../media/image.png?v=00000000">',
            '<meta itemprop="contentUrl" content="../media/image.png?v=00000000">',
        ]);
    }

    protected function inspectHtml(array $expectedStrings): void
    {
        StaticPageBuilder::handle($this->post);
        $stream = file_get_contents(Hyde::path('_site/posts/test-post.html'));

        foreach ($expectedStrings as $expectedString) {
            $this->assertStringContainsString($expectedString, $stream);
        }
    }
}
