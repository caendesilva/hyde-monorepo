<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Support\Models\DateString;
use Hyde\Framework\Features\Blogging\BlogPostDatePrefixHelper;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * High level test for the feature that allows dates to be set using prefixes in blog post filenames.
 *
 * @covers \Hyde\Framework\Features\Blogging\BlogPostDatePrefixHelper
 * @covers \Hyde\Framework\Factories\BlogPostDataFactory
 * @covers \Hyde\Support\Models\RouteKey
 *
 * @see \Hyde\Framework\Testing\Unit\BlogPostDatePrefixHelperUnitTest
 */
class BlogPostDatePrefixHelperTest extends TestCase
{
    public function testCanDetectDatePrefix()
    {
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-my-post.md'));
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-10-30-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('my-post.md'));
    }

    public function testCanExtractDateFromPrefix()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-my-post.md');
        $this->assertNotNull($date);
        $this->assertSame('2024-11-05', $date->format('Y-m-d'));

        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-10-30-my-post.md');
        $this->assertNotNull($date);
        $this->assertSame('2024-11-05 10:30', $date->format('Y-m-d H:i'));
    }

    public function testCanGetDateFromBlogPostFilename()
    {
        $this->file('_posts/2024-11-05-my-post.md', '# Hello World');
        $post = MarkdownPost::parse('2024-11-05-my-post');

        $this->assertInstanceOf(DateString::class, $post->date);
        $this->assertSame('2024-11-05 00:00', $post->date->string);
    }

    public function testCanGetDateFromBlogPostFilenameWithTime()
    {
        $this->file('_posts/2024-11-05-10-30-my-post.md', '# Hello World');
        $post = MarkdownPost::parse('2024-11-05-10-30-my-post');

        $this->assertInstanceOf(DateString::class, $post->date);
        $this->assertSame('2024-11-05 10:30', $post->date->string);
    }

    public function testDatePrefixIsStrippedFromRouteKey()
    {
        $this->file('_posts/2024-11-05-my-post.md', '# Hello World');
        $post = MarkdownPost::parse('2024-11-05-my-post');

        $this->assertSame('posts/my-post', $post->getRouteKey());
    }

    public function testDateFromPrefixIsUsedWhenNoFrontMatterDate()
    {
        $this->file('_posts/2024-11-05-my-post.md', '# Hello World');
        $post = MarkdownPost::parse('2024-11-05-my-post');

        $this->assertSame('2024-11-05 00:00', $post->date->string);
    }

    public function testFrontMatterDateTakesPrecedenceOverPrefix()
    {
        $this->file('_posts/2024-11-05-my-post.md', <<<'MD'
        ---
        date: "2024-12-25"
        ---
        # Hello World
        MD);

        $post = MarkdownPost::parse('2024-11-05-my-post');
        $this->assertSame('2024-12-25', $post->date->string);
    }
}
