<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Facades\Filesystem;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\Models\DateString;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Pages\MarkdownPost
 * @covers \Hyde\Framework\Factories\Concerns\HasFactory
 */
class MarkdownPostTest extends TestCase
{
    public function testConstructorCanCreateANewAuthorInstanceFromUsernameString()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => 'John Doe',
        ]));

        $this->assertInstanceOf(PostAuthor::class, $post->author);
        $this->assertSame('john_doe', $post->author->username);
        $this->assertSame('John Doe', $post->author->name);
        $this->assertNull($post->author->website);
    }

    public function testConstructorCanCreateANewAuthorInstanceFromUserArray()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => [
                'username' => 'john_doe',
                'name' => 'John Doe',
                'website' => 'https://example.com',
            ],
        ]));

        $this->assertInstanceOf(PostAuthor::class, $post->author);
        $this->assertSame('john_doe', $post->author->username);
        $this->assertSame('John Doe', $post->author->name);
        $this->assertSame('https://example.com', $post->author->website);
    }

    public function testAuthorRetrievalUsesNormalizedUsernameToFindTheRightAuthorRegardlessOfFormatting()
    {
        $postA = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => 'mr hyde',
        ]));

        $postB = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => 'Mr Hyde',
        ]));

        $postC = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => 'mr_hyde',
        ]));

        $this->assertAllSame($postA->author, $postB->author, $postC->author);
    }

    public function testConstructorCanCreateANewImageInstanceFromAString()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'image' => 'https://example.com/image.jpg',
        ]));

        $this->assertInstanceOf(FeaturedImage::class, $post->image);
        $this->assertSame('https://example.com/image.jpg', $post->image->getSource());
    }

    public function testConstructorCanCreateANewImageInstanceFromAnArray()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'image' => [
                'source' => 'https://example.com/image.jpg',
            ],
        ]));

        $this->assertInstanceOf(FeaturedImage::class, $post->image);
        $this->assertSame('https://example.com/image.jpg', $post->image->getSource());
    }

    public function testConstructorCanCreateANewDateStringInstanceFromMatter()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'date' => '2022-01-01',
        ]));

        $this->assertInstanceOf(DateString::class, $post->date);
        $this->assertSame('Jan 1st, 2022', $post->date->short);
    }

    public function testFeaturedImageCanBeConstructedReturnsNullWhenNoImageIsSetInThePageMatter()
    {
        $page = new MarkdownPost();
        $this->assertNull($page->image);
    }

    public function testFeaturedImageCanBeConstructedReturnsImageObjectWithLocalPathWhenMatterIsString()
    {
        $this->setupMediaFileAndCacheBusting();

        $page = MarkdownPost::make(matter: ['image' => 'foo.png']);
        $image = $page->image;
        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('media/foo.png', $image->getSource());
    }

    public function testFeaturedImageCanBeConstructedReturnsImageObjectWithLocalPathAndCacheBusting()
    {
        $this->setupMediaFileAndCacheBusting(true);

        $page = MarkdownPost::make(matter: ['image' => 'foo.png']);
        $image = $page->image;
        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('media/foo.png?v=98b41d87', $image->getSource());
    }

    public function testFeaturedImageCanBeConstructedReturnsImageObjectWithRemotePathWhenMatterIsString()
    {
        $this->setupMediaFileAndCacheBusting(true);

        $page = MarkdownPost::make(matter: ['image' => 'https://example.com/foo.png']);
        $image = $page->image;
        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('https://example.com/foo.png', $image->getSource());
    }

    public function testFeaturedImageCanBeConstructedReturnsImageObjectWithSuppliedDataWhenMatterIsArray()
    {
        $this->setupMediaFileAndCacheBusting();
        $page = MarkdownPost::make(matter: ['image' => ['source' => 'foo.png', 'titleText' => 'bar']]);
        $image = $page->image;
        $this->assertInstanceOf(FeaturedImage::class, $image);
        $this->assertSame('media/foo.png', $image->getSource());
        $this->assertSame('bar', $image->getTitleText());
    }

    public function testFeaturedImageThrowsExceptionWhenFileDoesNotExist()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [_media/nonexistent.png] not found when trying to resolve a media asset.');

        MarkdownPost::make(matter: ['image' => 'nonexistent.png']);
    }

    public function testFeaturedImageNormalizesPathForDifferentMediaDirectoryConfigurations()
    {
        Hyde::setMediaDirectory('assets');
        $this->file('assets/foo.png', 'test content');

        $page = MarkdownPost::make(matter: ['image' => 'foo.png']);
        $this->assertSame('assets/foo.png?v=98b41d87', $page->image->getSource());
    }

    public function testBlogPostCanBeCreatedWithoutFrontMatter()
    {
        file_put_contents(Hyde::path('_posts/test-post.md'), '# My New Post');

        StaticPageBuilder::handle(MarkdownPost::get('test-post'));

        $this->assertFileExists(Hyde::path('_site/posts/test-post.html'));

        Filesystem::unlink('_posts/test-post.md');
        Filesystem::unlink('_site/posts/test-post.html');
    }

    public function testBlogPostFeedCanBeRenderedWhenPostHasNoFrontMatter()
    {
        file_put_contents(Hyde::path('_posts/test-post.md'), '# My New Post');

        // Create a temporary page to test the feed
        copy(Hyde::vendorPath('resources/views/components/blog-post-feed.blade.php'),
            Hyde::path('_pages/feed-test.blade.php')
        );

        StaticPageBuilder::handle(BladePage::get('feed-test'));

        $this->assertFileExists(Hyde::path('_site/feed-test.html'));

        Filesystem::unlink('_posts/test-post.md');
        Filesystem::unlink('_pages/feed-test.blade.php');
        Filesystem::unlink('_site/feed-test.html');
    }

    protected function setupMediaFileAndCacheBusting(bool $enableCacheBusting = false): void
    {
        $this->file('_media/foo.png', 'test content');
        config(['hyde.cache_busting' => $enableCacheBusting]);
    }
}
