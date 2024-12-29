<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Author;
use Hyde\Pages\MarkdownPost;
use Hyde\Framework\Actions\CreatesNewMarkdownPostFile;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Test that the Author feature works in conjunction with the static Post generator.
 *
 * @see StaticSiteBuilderPostModuleTest
 */
class PostsAuthorIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('hyde.authors', []);
    }

    /**
     * Baseline test to create a post without a defined author, and assert that the username is displayed as is.
     *
     * Checks that the author was not defined, we do this by building the static site and inspecting the DOM.
     */
    public function testCreatePostWithUndefinedAuthor()
    {
        $this->createPostFile('post-with-undefined-author', 'test_undefined_author');

        $this->artisan('rebuild _posts/post-with-undefined-author.md')->assertExitCode(0);
        $this->assertFileExists(Hyde::path('_site/posts/post-with-undefined-author.html'));

        // Check that the author is rendered as is in the DOM
        $this->assertStringContainsString(
            '>Test Undefined Author</span>',
            file_get_contents(Hyde::path('_site/posts/post-with-undefined-author.html'))
        );
    }

    /**
     * Test that a defined author has its name injected into the DOM.
     */
    public function testCreatePostWithDefinedAuthorWithName()
    {
        $this->createPostFile('post-with-defined-author-with-name', 'named_author');

        Config::set('hyde.authors', [
            'named_author' => Author::create('Test Author', null),
        ]);

        $this->artisan('rebuild _posts/post-with-defined-author-with-name.md')->assertExitCode(0);
        $this->assertFileExists(Hyde::path('_site/posts/post-with-defined-author-with-name.html'));

        // Check that the author is contains the set name in the DOM
        $this->assertStringContainsString(
            '<span itemprop="name" aria-label="The author\'s name" title=@named_author>Test Author</span>',
            file_get_contents(Hyde::path('_site/posts/post-with-defined-author-with-name.html'))
        );
    }

    /**
     * Test that a defined author with website has its site linked.
     */
    public function testCreatePostWithDefinedAuthorWithWebsite()
    {
        $this->createPostFile('post-with-defined-author-with-name', 'test_author_with_website');

        Config::set('hyde.authors', [
            'test_author_with_website' => Author::create('Test Author', 'https://example.org'),
        ]);

        $this->artisan('rebuild _posts/post-with-defined-author-with-name.md')->assertExitCode(0);
        $this->assertFileExists(Hyde::path('_site/posts/post-with-defined-author-with-name.html'));

        // Check that the author is contains the set name in the DOM
        $this->assertStringContainsString(
            '<span itemprop="name" aria-label="The author\'s name" title=@test_author_with_website>Test Author</span>',
            file_get_contents(Hyde::path('_site/posts/post-with-defined-author-with-name.html'))
        );

        // Check that the author is contains the set website in the DOM
        $this->assertStringContainsString(
            '<a href="https://example.org" rel="author" itemprop="url" aria-label="The author\'s website">',
            file_get_contents(Hyde::path('_site/posts/post-with-defined-author-with-name.html'))
        );
    }

    public function testAllPostAuthorFieldsCanBeSetInFrontMatter()
    {
        $this->file('_posts/post-with-all-author-fields.md', <<<'MD'
            ---
            author:
                username: mr_hyde
                name: Mr. Hyde
                website: https://hydephp.com
                bio: The mysterious author of HydePHP
                avatar: avatar.png
                socials:
                    twitter: "@HydeFramework"
                    github: hydephp
            ---
            
            # Post with all author fields
            MD
        );

        $this->artisan('rebuild _posts/post-with-all-author-fields.md')->assertExitCode(0);
        $this->cleanUpWhenDone('_site/posts/post-with-all-author-fields.html');
        $this->assertFileExists(Hyde::path('_site/posts/post-with-all-author-fields.html'));

        $page = MarkdownPost::get('post-with-all-author-fields');
        $this->assertNotNull($page);
        $this->assertNotNull($page->author);

        $this->assertSame([
            'username' => 'mr_hyde',
            'name' => 'Mr. Hyde',
            'website' => 'https://hydephp.com',
            'bio' => 'The mysterious author of HydePHP',
            'avatar' => 'avatar.png',
            'socials' => [
                'twitter' => '@HydeFramework',
                'github' => 'hydephp',
            ],
        ], $page->author->toArray());
    }

    public function testConfiguredPostAuthorFieldsCanBeOverriddenInFrontMatter()
    {
        Config::set('hyde.authors', [
            'mr_hyde' => Author::create(
                name: 'Mr. Hyde',
                website: 'https://hydephp.com',
                bio: 'The mysterious author of HydePHP',
                avatar: 'avatar.png',
                socials: [
                    'twitter' => '@HydeFramework',
                    'github' => 'hydephp',
                ],
            ),
        ]);

        $this->file('_posts/literal.md', <<<'MD'
            ---
            author: mr_hyde
            ---
            
            # Using the configured author
            MD
        );

        $this->file('_posts/changed.md', <<<'MD'
            ---
            author:
                username: mr_hyde
                name: Dr. Jekyll
            ---
            
            # Modifying the configured author
            MD
        );

        $this->artisan('rebuild _posts/literal.md')->assertExitCode(0);
        $this->artisan('rebuild _posts/changed.md')->assertExitCode(0);
        $this->assertFileExists(Hyde::path('_site/posts/literal.html'));
        $this->assertFileExists(Hyde::path('_site/posts/changed.html'));
        $this->cleanUpWhenDone('_site/posts/literal.html');
        $this->cleanUpWhenDone('_site/posts/changed.html');

        $page = MarkdownPost::get('literal');
        $this->assertNotNull($page);
        $this->assertNotNull($page->author);

        $this->assertSame([
            'username' => 'mr_hyde',
            'name' => 'Mr. Hyde',
            'website' => 'https://hydephp.com',
            'bio' => 'The mysterious author of HydePHP',
            'avatar' => 'avatar.png',
            'socials' => [
                'twitter' => '@HydeFramework',
                'github' => 'hydephp',
            ],
        ], $page->author->toArray());

        $page = MarkdownPost::get('changed');
        $this->assertNotNull($page);
        $this->assertNotNull($page->author);

        $this->assertSame([
            'username' => 'mr_hyde',
            'name' => 'Dr. Jekyll',
            // The original fields are not overwritten
        ], $page->author->toArray());
    }

    protected function createPostFile(string $title, string $author): void
    {
        (new CreatesNewMarkdownPostFile($title, '', '', $author))->save();

        $this->assertFileExists(Hyde::path("_posts/$title.md"));

        $this->cleanUpWhenDone("_posts/$title.md");
        $this->cleanUpWhenDone("_site/posts/$title.html");
    }
}
