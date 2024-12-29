<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Services;

use SimpleXMLElement;
use Hyde\Facades\Filesystem;
use Hyde\Framework\Features\XmlGenerators\SitemapGenerator;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\File;

/**
 * @covers \Hyde\Framework\Features\XmlGenerators\SitemapGenerator
 * @covers \Hyde\Framework\Features\XmlGenerators\BaseXmlGenerator
 */
class SitemapServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(Hyde::path('_pages'));
        File::makeDirectory(Hyde::path('_pages'));

        copy(Hyde::vendorPath('resources/views/homepages/welcome.blade.php'), Hyde::path('_pages/index.blade.php'));
        copy(Hyde::vendorPath('resources/views/pages/404.blade.php'), Hyde::path('_pages/404.blade.php'));
    }

    public function testServiceInstantiatesXmlElement()
    {
        $service = new SitemapGenerator();
        $this->assertInstanceOf(SimpleXMLElement::class, $service->getXmlElement());
    }

    public function testGenerateAddsDefaultPagesToXml()
    {
        $service = new SitemapGenerator();
        $service->generate();

        // The test runner has an index and 404 page, so we are using that as a baseline
        $this->assertCount(2, $service->getXmlElement()->url);
    }

    public function testGenerateAddsMarkdownPagesToXml()
    {
        Filesystem::touch('_pages/foo.md');

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertCount(3, $service->getXmlElement()->url);

        Filesystem::unlink('_pages/foo.md');
    }

    public function testGenerateAddsMarkdownPostsToXml()
    {
        Filesystem::touch('_posts/foo.md');

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertCount(3, $service->getXmlElement()->url);

        Filesystem::unlink('_posts/foo.md');
    }

    public function testGenerateAddsDocumentationPagesToXml()
    {
        $this->withoutDocumentationSearch();
        Filesystem::touch('_docs/foo.md');

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertCount(3, $service->getXmlElement()->url);

        Filesystem::unlink('_docs/foo.md');
        $this->restoreDocumentationSearch();
    }

    public function test_generate_adds_documentation_search_pages_to_xml()
    {
        Filesystem::touch('_docs/foo.md');

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertCount(5, $service->getXmlElement()->url);

        Filesystem::unlink('_docs/foo.md');
    }

    public function testGetXmlReturnsXmlString()
    {
        $service = new SitemapGenerator();
        $service->generate();

        $xml = $service->getXml();

        $this->assertIsString($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
    }

    public function testGenerateSitemapShorthandMethodReturnsXmlString()
    {
        $xml = SitemapGenerator::make();

        $this->assertIsString($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
    }

    public function testUrlItemIsGeneratedCorrectly()
    {
        config(['hyde.pretty_urls' => false]);
        $this->withSiteUrl();

        Filesystem::touch('_pages/0-test.blade.php');

        $service = new SitemapGenerator();
        $service->generate();

        $url = $service->getXmlElement()->url[0];

        $this->assertEquals('https://example.com/0-test.html', $url->loc);
        $this->assertEquals('daily', $url->changefreq);
        $this->assertTrue(isset($url->lastmod));

        Filesystem::unlink('_pages/0-test.blade.php');
    }

    public function testUrlItemIsGeneratedWithPrettyUrlsIfEnabled()
    {
        config(['hyde.pretty_urls' => true]);
        $this->withSiteUrl();

        Filesystem::touch('_pages/0-test.blade.php');

        $service = new SitemapGenerator();
        $service->generate();

        $url = $service->getXmlElement()->url[0];
        $this->assertEquals('https://example.com/0-test', $url->loc);

        Filesystem::unlink('_pages/0-test.blade.php');
    }

    public function testAllRouteTypesAreDiscovered()
    {
        config(['hyde.url' => 'foo']);

        Filesystem::unlink(['_pages/index.blade.php', '_pages/404.blade.php']);
        $this->withoutDocumentationSearch();

        $files = [
            '_pages/blade.blade.php',
            '_pages/markdown.md',
            '_pages/html.html',
            '_posts/post.md',
            '_docs/doc.md',
        ];

        Filesystem::touch($files);

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertCount(5, $service->getXmlElement()->url);

        $this->assertEquals('foo/html.html', $service->getXmlElement()->url[0]->loc);
        $this->assertEquals('foo/blade.html', $service->getXmlElement()->url[1]->loc);
        $this->assertEquals('foo/markdown.html', $service->getXmlElement()->url[2]->loc);
        $this->assertEquals('foo/posts/post.html', $service->getXmlElement()->url[3]->loc);
        $this->assertEquals('foo/docs/doc.html', $service->getXmlElement()->url[4]->loc);

        Filesystem::unlink($files);

        copy(Hyde::vendorPath('resources/views/homepages/welcome.blade.php'), Hyde::path('_pages/index.blade.php'));
        copy(Hyde::vendorPath('resources/views/pages/404.blade.php'), Hyde::path('_pages/404.blade.php'));

        $this->restoreDocumentationSearch();
    }

    public function testLinksFallbackToRelativeLinksWhenASiteUrlIsNotSet()
    {
        $this->withoutSiteUrl();

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertEquals('404.html', $service->getXmlElement()->url[0]->loc);
        $this->assertEquals('index.html', $service->getXmlElement()->url[1]->loc);
    }

    public function testLinksFallbackToRelativeLinksWhenSiteUrlIsLocalhost()
    {
        config(['hyde.url' => 'http://localhost']);

        $service = new SitemapGenerator();
        $service->generate();

        $this->assertEquals('404.html', $service->getXmlElement()->url[0]->loc);
        $this->assertEquals('index.html', $service->getXmlElement()->url[1]->loc);
    }
}
