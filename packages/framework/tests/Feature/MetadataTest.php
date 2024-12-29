<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Meta;
use Hyde\Pages\BladePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Framework\Features\Metadata\Elements\LinkElement;
use Hyde\Framework\Features\Metadata\Elements\MetadataElement;
use Hyde\Framework\Features\Metadata\Elements\OpenGraphElement;
use Hyde\Framework\Features\Metadata\MetadataBag;
use Hyde\Hyde;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Features\Metadata\MetadataBag
 * @covers \Hyde\Framework\Features\Metadata\PageMetadataBag
 * @covers \Hyde\Framework\Features\Metadata\GlobalMetadataBag
 * @covers \Hyde\Framework\Features\Metadata\Elements\LinkElement
 * @covers \Hyde\Framework\Features\Metadata\Elements\MetadataElement
 * @covers \Hyde\Framework\Features\Metadata\Elements\OpenGraphElement
 */
class MetadataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutSiteUrl();
        config(['hyde.meta' => []]);
        config(['hyde.rss.enabled' => false]);
        config(['hyde.generate_sitemap' => false]);
    }

    protected function assertPageHasMetadata(HydePage $page, string $metadata)
    {
        $this->assertStringContainsString(
            $metadata,
            $page->metadata->render()
        );
    }

    protected function assertPageDoesNotHaveMetadata(HydePage $page, string $metadata)
    {
        $this->assertStringNotContainsString(
            $metadata,
            $page->metadata->render()
        );
    }

    public function testMetadataObjectIsGeneratedAutomatically()
    {
        $page = new MarkdownPage();

        $this->assertNotNull($page->metadata);
        $this->assertInstanceOf(MetadataBag::class, $page->metadata);
        $this->assertSame([], $page->metadata->get());
    }

    public function testLinkItemModel()
    {
        $item = new LinkElement('rel', 'href');

        $this->assertSame('rel', $item->uniqueKey());
        $this->assertSame('<link rel="rel" href="href">', (string) $item);

        $item = new LinkElement('rel', 'href', ['attr' => 'value']);
        $this->assertSame('<link rel="rel" href="href" attr="value">', (string) $item);
    }

    public function testMetadataItemModel()
    {
        $item = new MetadataElement('name', 'content');

        $this->assertSame('name', $item->uniqueKey());
        $this->assertSame('<meta name="name" content="content">', (string) $item);
    }

    public function testOpenGraphItemModel()
    {
        $item = new OpenGraphElement('property', 'content');

        $this->assertSame('property', $item->uniqueKey());
        $this->assertSame('<meta property="og:property" content="content">', (string) $item);

        $item = new OpenGraphElement('og:property', 'content');
        $this->assertSame('<meta property="og:property" content="content">', (string) $item);
    }

    public function testLinkItemCanBeAdded()
    {
        $page = new MarkdownPage();
        $page->metadata->add(Meta::link('foo', 'bar'));

        $this->assertEquals([
            'links:foo' => Meta::link('foo', 'bar'),
        ], $page->metadata->get());
    }

    public function testMetadataItemCanBeAdded()
    {
        $page = new MarkdownPage();
        $page->metadata->add(Meta::name('foo', 'bar'));

        $this->assertEquals([
            'metadata:foo' => Meta::name('foo', 'bar'),
        ], $page->metadata->get());
    }

    public function testOpenGraphItemCanBeAdded()
    {
        $page = new MarkdownPage();
        $page->metadata->add(Meta::property('foo', 'bar'));

        $this->assertEquals([
            'properties:foo' => Meta::property('foo', 'bar'),
        ], $page->metadata->get());
    }

    public function testGenericItemCanBeAdded()
    {
        $page = new MarkdownPage();
        $page->metadata->add('foo');

        $this->assertEquals([
            'generics:0' => 'foo',
        ], $page->metadata->get());
    }

    public function testMultipleItemsCanBeAccessedWithGetMethod()
    {
        $page = new MarkdownPage();
        $page->metadata->add(Meta::link('foo', 'bar'));
        $page->metadata->add(Meta::name('foo', 'bar'));
        $page->metadata->add(Meta::property('foo', 'bar'));
        $page->metadata->add('foo');

        $this->assertEquals([
            'links:foo' => Meta::link('foo', 'bar'),
            'metadata:foo' => Meta::name('foo', 'bar'),
            'properties:foo' => Meta::property('foo', 'bar'),
            'generics:0' => 'foo',
        ], $page->metadata->get());
    }

    public function testMultipleItemsOfSameKeyAndTypeOnlyKeepsLatest()
    {
        $page = new MarkdownPage();
        $page->metadata->add(Meta::link('foo', 'bar'));
        $page->metadata->add(Meta::link('foo', 'baz'));

        $this->assertEquals([
            'links:foo' => Meta::link('foo', 'baz'),
        ], $page->metadata->get());
    }

    public function testRenderReturnsHtmlStringOfImplodedMetadataArrays()
    {
        $page = new MarkdownPage();

        $page->metadata->add(Meta::link('foo', 'bar'));
        $page->metadata->add(Meta::name('foo', 'bar'));
        $page->metadata->add(Meta::property('foo', 'bar'));
        $page->metadata->add('foo');

        $this->assertSame(implode("\n", [
            '<link rel="foo" href="bar">',
            '<meta name="foo" content="bar">',
            '<meta property="og:foo" content="bar">',
            'foo',
        ]),
            $page->metadata->render());
    }

    public function testCustomMetadataOverridesConfigDefinedMetadata()
    {
        config(['hyde.meta' => [
            Meta::name('foo', 'bar'),
        ]]);

        $page = new MarkdownPage();
        $page->metadata->add(Meta::name('foo', 'baz'));

        $this->assertEquals([
            'metadata:foo' => Meta::name('foo', 'baz'),
        ], $page->metadata->get());
    }

    public function testDynamicMetadataOverridesConfigDefinedMetadata()
    {
        config(['hyde.meta' => [
            Meta::name('twitter:title', 'bar'),
        ]]);

        $page = new MarkdownPage(matter: ['title' => 'baz']);

        $this->assertEquals([
            'metadata:twitter:title' => Meta::name('twitter:title', 'HydePHP - baz'),
            'properties:title' => Meta::property('title', 'HydePHP - baz'),
        ], $page->metadata->get());
    }

    public function testDoesNotAddCanonicalLinkWhenBaseUrlIsNotSet()
    {
        $this->withoutSiteUrl();

        $page = new MarkdownPage('bar');

        $this->assertStringNotContainsString('<link rel="canonical"', $page->metadata->render());
    }

    public function testDoesNotAddCanonicalLinkWhenIdentifierIsNotSet()
    {
        config(['hyde.url' => 'foo']);

        $page = new MarkdownPage();

        $this->assertStringNotContainsString('<link rel="canonical"', $page->metadata->render());
    }

    public function testAddsCanonicalLinkWhenBaseUrlAndIdentifierIsSet()
    {
        config(['hyde.url' => 'foo']);

        $page = new MarkdownPage('bar');

        $this->assertStringContainsString('<link rel="canonical" href="foo/bar.html">', $page->metadata->render());
    }

    public function testCanonicalLinkUsesCleanUrlSetting()
    {
        config(['hyde.url' => 'foo']);
        config(['hyde.pretty_urls' => true]);

        $page = new MarkdownPage('bar');

        $this->assertStringContainsString('<link rel="canonical" href="foo/bar">', $page->metadata->render());
    }

    public function testCanOverrideCanonicalLinkWithFrontMatter()
    {
        config(['hyde.url' => 'foo']);

        $page = new MarkdownPage('bar', [
            'canonicalUrl' => 'canonical',
        ]);

        $this->assertStringContainsString('<link rel="canonical" href="canonical">', $page->metadata->render());
    }

    public function testAddsTwitterAndOpenGraphTitleWhenTitleIsSet()
    {
        $page = new MarkdownPage(matter: ['title' => 'Foo Bar']);

        $this->assertSame(
            '<meta name="twitter:title" content="HydePHP - Foo Bar">'."\n".
            '<meta property="og:title" content="HydePHP - Foo Bar">',
            $page->metadata->render()
        );
    }

    public function testDoesNotAddTwitterAndOpenGraphTitleWhenNoTitleIsSet()
    {
        $page = new MarkdownPage(matter: ['title' => null]);

        $this->assertSame('', $page->metadata->render());
    }

    public function testAddsDescriptionWhenDescriptionIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['description' => 'My Description']);

        $this->assertPageHasMetadata($page, '<meta name="description" content="My Description">');
        $this->assertPageHasMetadata($page, '<meta property="og:description" content="My Description">');
    }

    public function testDoesNotAddDescriptionWhenDescriptionIsNotSetInPost()
    {
        $page = new MarkdownPost();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="description"');
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:description"');
    }

    public function testAddsDescriptionWhenDescriptionIsSetInMarkdownPage()
    {
        $page = new MarkdownPage(matter: ['description' => 'My Page Description']);

        $this->assertPageHasMetadata($page, '<meta name="description" content="My Page Description">');
        $this->assertPageHasMetadata($page, '<meta property="og:description" content="My Page Description">');
    }

    public function testDoesNotAddDescriptionWhenDescriptionIsNotSetInMarkdownPage()
    {
        $page = new MarkdownPage();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="description"');
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:description"');
    }

    public function testAddsDescriptionWhenDescriptionIsSetInBladePage()
    {
        $page = new BladePage(matter: ['description' => 'My Page Description']);

        $this->assertPageHasMetadata($page, '<meta name="description" content="My Page Description">');
        $this->assertPageHasMetadata($page, '<meta property="og:description" content="My Page Description">');
    }

    public function testDoesNotAddDescriptionWhenDescriptionIsNotSetInBladePage()
    {
        $page = new BladePage();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="description"');
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:description"');
    }

    public function testAddsDescriptionWhenDescriptionIsSetInDocumentationPage()
    {
        $page = new DocumentationPage(matter: ['description' => 'My Page Description']);

        $this->assertPageHasMetadata($page, '<meta name="description" content="My Page Description">');
        $this->assertPageHasMetadata($page, '<meta property="og:description" content="My Page Description">');
    }

    public function testDoesNotAddDescriptionWhenDescriptionIsNotSetInDocumentationPage()
    {
        $page = new DocumentationPage();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="description"');
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:description"');
    }

    public function testAddsAuthorWhenAuthorIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['author' => 'My Author']);

        $this->assertPageHasMetadata($page, '<meta name="author" content="My Author">');
    }

    public function testDoesNotAddAuthorWhenAuthorIsNotSetInPost()
    {
        $page = new MarkdownPost();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="author"');
    }

    public function testAddsKeywordsWhenCategoryIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['category' => 'My Category']);

        $this->assertPageHasMetadata($page, '<meta name="keywords" content="My Category">');
    }

    public function testDoesNotAddKeywordsWhenCategoryIsNotSetInPost()
    {
        $page = new MarkdownPost();

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="keywords"');
    }

    public function testAddsUrlPropertyWhenCanonicalUrlIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['canonicalUrl' => 'example.html']);

        $this->assertPageHasMetadata($page, '<meta property="og:url" content="example.html">');
    }

    public function testDoesNotAddUrlPropertyWhenCanonicalUrlIsNotSetInPost()
    {
        $page = new MarkdownPost();

        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:url"');
    }

    public function testDoesNotAddUrlPropertyWhenCanonicalUrlIsNull()
    {
        $page = new MarkdownPost(matter: ['canonicalUrl' => null]);

        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:url"');
    }

    public function testAddsTitlePropertyWhenTitleIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['title' => 'My Title']);

        $this->assertPageHasMetadata($page, '<meta property="og:title" content="HydePHP - My Title">');
    }

    public function testDoesNotAddTitlePropertyWhenTitleIsNotSetInPost()
    {
        $page = new MarkdownPost();

        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:title"');
    }

    public function testAddsPublishedTimePropertyWhenDateIsSetInPost()
    {
        $page = new MarkdownPost(matter: ['date' => '2022-01-01']);

        $this->assertPageHasMetadata($page, '<meta property="og:article:published_time" content="2022-01-01T00:00:00+00:00">');
    }

    public function testDoesNotAddPublishedTimePropertyWhenDateIsNotSetInPost()
    {
        $page = new MarkdownPost();
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:article:published_time"');
    }

    public function testAddsTypePropertyAutomatically()
    {
        $page = new MarkdownPost();

        $this->assertPageHasMetadata($page, '<meta property="og:type" content="article">');
    }

    public function testDynamicPostMetaPropertiesReturnsBaseArrayWhenInitializedWithEmptyFrontMatter()
    {
        $page = new MarkdownPost();

        $this->assertSame('<meta property="og:type" content="article">', $page->metadata->render());
    }

    public function testDoesNotAddImagePropertyWhenImageIsNotSetInPost()
    {
        $page = new MarkdownPost();
        $this->assertPageDoesNotHaveMetadata($page, '<meta property="og:image"');
    }

    public function testAddsImagePropertyWhenImageIsSetInPost()
    {
        $this->file('_media/image.jpg');
        $page = new MarkdownPost(matter: ['image' => 'image.jpg']);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../media/image.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageMetadataWhenFeaturedImageSetToString()
    {
        $this->file('_media/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkThatIsAlwaysRelative()
    {
        $this->file('_media/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkThatIsAlwaysRelativeForNestedPosts()
    {
        $this->file('_media/foo.jpg');

        $page = new MarkdownPost('foo/bar', matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkThatIsAlwaysRelativeForNestedOutputDirectories()
    {
        MarkdownPost::setOutputDirectory('_posts/foo');

        $this->file('_media/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkThatIsAlwaysRelativeForNestedPostsAndNestedOutputDirectories()
    {
        MarkdownPost::setOutputDirectory('_posts/foo');

        $this->file('_media/foo.jpg');

        $page = new MarkdownPost('bar/baz', matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../../../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkThatUsesTheConfiguredMediaDirectory()
    {
        Hyde::setMediaDirectory('assets');

        $this->file('assets/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../assets/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageMetadataWhenFeaturedImageSetToArrayWithPath()
    {
        $this->file('_media/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => [
                'source' => 'foo.jpg',
            ],
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../media/foo.jpg?v=00000000">');
    }

    public function testDynamicPostMetaPropertiesContainsImageLinkWithoutCacheBusting()
    {
        config(['hyde.cache_busting' => false]);

        $this->file('_media/foo.jpg');

        $page = new MarkdownPost(matter: [
            'image' => 'foo.jpg',
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="../media/foo.jpg">');
    }

    public function testDynamicPostMetaPropertiesContainsImageMetadataWhenFeaturedImageSetToArrayWithUrl()
    {
        $page = new MarkdownPost(matter: [
            'image' => [
                'source' => 'https://example.com/foo.jpg',
            ],
        ]);

        $this->assertPageHasMetadata($page, '<meta property="og:image" content="https://example.com/foo.jpg">');
    }

    public function testDynamicPostAuthorReturnsAuthorNameWhenAuthorSetToArrayUsingUsername()
    {
        $page = new MarkdownPost(matter: [
            'author' => [
                'username' => 'username',
            ],
        ]);

        $this->assertPageHasMetadata($page, '<meta name="author" content="Username">');
    }

    public function testDynamicPostAuthorReturnsAuthorNameWhenAuthorSetToArrayUsingName()
    {
        $page = new MarkdownPost(matter: [
            'author' => [
                'name' => 'Name',
            ],
        ]);

        $this->assertPageHasMetadata($page, '<meta name="author" content="Name">');
    }

    public function testNoAuthorIsSetWhenAuthorSetToArrayWithoutNameOrUsername()
    {
        $page = new MarkdownPost(matter: [
            'author' => [],
        ]);

        $this->assertPageDoesNotHaveMetadata($page, '<meta name="author"');
    }
}
