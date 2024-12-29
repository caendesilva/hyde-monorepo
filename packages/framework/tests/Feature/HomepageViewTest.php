<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Testing\TestCase;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestsBladeViews;

/**
 * @coversNothing Test to ensure all homepages can be rendered
 */
class HomepageViewTest extends TestCase
{
    use TestsBladeViews;

    public function testBlankHomepage()
    {
        Hyde::shareViewData(new BladePage('index'));

        $view = $this->view(view('hyde::homepages.blank'));

        $view->assertSee('HydePHP')
            ->assertSee('Hello World!');
    }

    public function testWelcomeHomepage()
    {
        Hyde::shareViewData(new BladePage('index'));

        $view = $this->view(view('hyde::homepages.welcome'));

        $view->assertSee('HydePHP')
            ->assertSee('Welcome to HydePHP!');
    }

    public function testPostFeedHomepage()
    {
        Hyde::shareViewData(new BladePage('index'));

        $view = $this->view(view('hyde::homepages.post-feed'));

        $view->assertSee('HydePHP')
            ->assertSee('Latest Posts')
            ->assertSeeHtml('id="post-feed"');
    }

    public function testPostFeedHomepageWithPosts()
    {
        Hyde::shareViewData(new BladePage('index'));

        Hyde::pages()->add(new MarkdownPost('hello-world', ['author' => 'mr_hyde'], 'Hello World!'));

        $view = $this->view(view('hyde::homepages.post-feed'));

        $view->assertSee('HydePHP')
            ->assertSee('Latest Posts')
            ->assertSeeHtml('id="post-feed"')
            ->assertSeeHtml('<a href="posts/hello-world.html"')
            ->assertSee('Hello World!')
            ->assertSee('Mr. Hyde');
    }
}
