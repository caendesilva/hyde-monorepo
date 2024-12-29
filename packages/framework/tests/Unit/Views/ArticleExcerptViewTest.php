<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Hyde;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

/**
 * @see resources/views/components/article-excerpt.blade.php
 */
class ArticleExcerptViewTest extends TestCase
{
    protected function renderTestView(MarkdownPost $post): string
    {
        return Blade::render(file_get_contents(
            Hyde::vendorPath('resources/views/components/article-excerpt.blade.php')
        ), ['post' => $post]);
    }

    public function testComponentCanBeRendered()
    {
        $view = $this->renderTestView(MarkdownPost::make());
        $this->assertStringContainsString('https://schema.org/BlogPosting', $view);
    }

    public function testComponentRendersPostData()
    {
        $view = $this->renderTestView(MarkdownPost::make(matter: [
            'title' => 'Test Post',
            'date' => '2022-01-01 12:00:00',
            'author' => 'John Doe',
            'description' => 'This is a test post.',
        ]));

        $this->assertStringContainsString('Test Post', $view);
        $this->assertStringContainsString('Jan 1st, 2022', $view);
        $this->assertStringContainsString('John Doe', $view);
        $this->assertStringContainsString('This is a test post.', $view);
        $this->assertStringContainsString('Read post', $view);
    }

    public function testComponentRendersPostWithAuthorObject()
    {
        $view = $this->renderTestView(MarkdownPost::make(matter: [
            'author' => [
                'name' => 'John Doe',
            ],
        ]));

        $this->assertStringContainsString('John Doe', $view);
    }

    public function testMetadataHasIsoDate()
    {
        $view = $this->renderTestView(MarkdownPost::make(matter: [
            'date' => '2022-01-01',
        ]));

        $this->assertStringContainsString('<time itemprop="dateCreated datePublished" datetime="2022-01-01T00:00:00+00:00">Jan 1st, 2022</time>', $view);
    }

    public function testDateIsNotAddedWhenNotSet()
    {
        $view = $this->renderTestView(MarkdownPost::make());

        $this->assertStringNotContainsString('<time', $view);
    }

    public function testThereIsNoCommaAfterDateStringWhenThereIsNoAuthor()
    {
        $view = $this->renderTestView(MarkdownPost::make(matter: [
            'date' => '2022-01-01',
        ]));

        $this->assertStringContainsString('Jan 1st, 2022</time>', $view);
        $this->assertStringNotContainsString('Jan 1st, 2022</time>,', $view);
    }

    public function testThereIsACommaAfterDateStringWhenThereIsAAuthor()
    {
        $view = $this->renderTestView(MarkdownPost::make(matter: [
            'date' => '2022-01-01',
            'author' => 'John Doe',
        ]));

        $this->assertStringContainsString('Jan 1st, 2022</time>,', $view);
    }

    public function testItempropImageIsNotAddedWhenThereIsNoImage()
    {
        $view = $this->renderTestView(MarkdownPost::make());
        $this->assertStringNotContainsString('itemprop="image"', $view);
    }

    public function testItempropImageIsAddedWhenThereIsAnImageWithLocalPath()
    {
        $this->file('_media/image.jpg');

        $viewLocal = $this->renderTestView(MarkdownPost::make(matter: [
            'image' => 'image.jpg',
        ]));

        $this->assertStringContainsString('itemprop="image"', $viewLocal);
        $this->assertStringContainsString('content="media/image.jpg?v=00000000"', $viewLocal);
    }

    public function testItempropImageIsAddedWhenThereIsAnImageWithLocalPathNoCache()
    {
        $this->file('_media/image.jpg');

        config(['hyde.cache_busting' => false]);

        $viewLocalNoCache = $this->renderTestView(MarkdownPost::make(matter: [
            'image' => 'image.jpg',
        ]));

        $this->assertStringContainsString('itemprop="image"', $viewLocalNoCache);
        $this->assertStringContainsString('content="media/image.jpg"', $viewLocalNoCache);
    }

    public function testItempropImageIsAddedWhenThereIsAnImageWithRemoteUrl()
    {
        $viewRemote = $this->renderTestView(MarkdownPost::make(matter: [
            'image' => 'https://example.com/image.jpg',
        ]));

        $this->assertStringContainsString('itemprop="image"', $viewRemote);
        $this->assertStringContainsString('content="https://example.com/image.jpg"', $viewRemote);
    }
}
