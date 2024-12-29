<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestsBladeViews;

/**
 * @coversNothing Test to ensure the blog post feed component can be rendered
 */
class BlogPostFeedComponentViewTest extends TestCase
{
    use TestsBladeViews;

    public function testPostFeedWithoutPosts()
    {
        $view = $this->view(view('hyde::components.blog-post-feed'));

        $view->assertSeeHtml('<ol itemscope itemtype="https://schema.org/ItemList">')
            ->assertDontSee('<li')
            ->assertDontSee('<article')
            ->assertSeeHtml('</ol>');
    }

    public function testPostFeedWithSinglePost()
    {
        Hyde::pages()->add(new MarkdownPost('hello-world', ['author' => 'mr_hyde'], 'Hello World!'));

        $view = $this->view(view('hyde::components.blog-post-feed'));

        $view->assertSeeHtml('<ol itemscope itemtype="https://schema.org/ItemList">')
            ->assertSeeHtml('<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"')
            ->assertSeeHtml('<meta itemprop="position" content="1">')
            ->assertSeeHtml('<article itemprop="item" itemscope itemtype="https://schema.org/BlogPosting">')
            ->assertSeeHtml('<meta itemprop="identifier" content="hello-world">')
            ->assertSeeHtml('<a href="posts/hello-world.html"')
            ->assertSeeHtml('<h2 itemprop="headline"')
            ->assertSee('Hello World')
            ->assertSeeHtml('<span itemprop="author" itemscope itemtype="https://schema.org/Person">')
            ->assertSee('Mr. Hyde')
            ->assertSeeHtml('<p itemprop="description"')
            ->assertSee('Hello World!')
            ->assertSeeHtml('<a href="posts/hello-world.html"')
            ->assertSee('Read post')
            ->assertSeeHtml('</article>')
            ->assertSeeHtml('</li>')
            ->assertSeeHtml('</ol>');
    }

    public function testPostFeedWithMultiplePosts()
    {
        Hyde::pages()->add(new MarkdownPost('hello-world', ['author' => 'mr_hyde'], 'Hello World!'));
        Hyde::pages()->add(new MarkdownPost('second-post', ['author' => 'jane_doe'], 'Another post content'));

        $view = $this->view(view('hyde::components.blog-post-feed'));

        $view->assertSeeHtml('<ol itemscope itemtype="https://schema.org/ItemList">')
            ->assertSeeHtml('<meta itemprop="position" content="1">')
            ->assertSeeHtml('<meta itemprop="position" content="2">')
            ->assertSee('Hello World')
            ->assertSee('Mr. Hyde')
            ->assertSee('Another post content')
            ->assertSee('Jane Doe')
            ->assertSeeHtml('</ol>');
    }

    public function testPostFeedWithCustomPosts()
    {
        Hyde::pages()->add(new MarkdownPost('global', ['author' => 'default'], 'Ignored post content'));

        $customPosts = [
            new MarkdownPost('hello-world', ['author' => 'mr_hyde'], 'Hello World!'),
            new MarkdownPost('second-post', ['author' => 'jane_doe'], 'Another post content'),
        ];

        $view = $this->view(view('hyde::components.blog-post-feed', [
            'posts' => $customPosts,
        ]));

        $view->assertSeeHtml('<ol itemscope itemtype="https://schema.org/ItemList">')
            ->assertDontSee('Ignored post content')
            ->assertSee('Hello World')
            ->assertSee('Mr. Hyde')
            ->assertSee('Another post content')
            ->assertSee('Jane Doe')
            ->assertSeeHtml('</ol>');
    }
}
