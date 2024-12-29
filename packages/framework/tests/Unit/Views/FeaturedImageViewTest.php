<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Hyde;
use Hyde\Framework\Factories\FeaturedImageFactory;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * @see resources/views/components/post/image.blade.php
 */
class FeaturedImageViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->file('_media/foo.jpg', 'test content');
        config(['hyde.cache_busting' => false]);
    }

    public function testTheView()
    {
        $component = $this->renderComponent([
            'image.source' => 'foo.jpg',
            'image.altText' => 'This is an image',
            'image.titleText' => 'FeaturedImage Title',
            'image.authorName' => 'John Doe',
            'image.licenseName' => 'Creative Commons',
            'image.licenseUrl' => 'https://licence.example.com',
        ]);

        $this->assertStringContainsString('src="media/foo.jpg"', $component);
        $this->assertStringContainsString('alt="This is an image"', $component);
        $this->assertStringContainsString('title="FeaturedImage Title"', $component);
        $this->assertStringContainsString('Image by', $component);
        $this->assertStringContainsString('John Doe', $component);
        $this->assertStringContainsString('License', $component);
        $this->assertStringContainsString('Creative Commons', $component);
        $this->assertStringContainsString('href="https://licence.example.com" rel="license nofollow noopener"', $component);

        $this->assertEquals(
            $this->stripWhitespace('Image by John Doe. License Creative Commons.'),
            $this->stripWhitespace($this->stripHtml($component))
        );
    }

    public function testImageAuthorAttributionString()
    {
        $string = $this->renderComponent(['image.authorName' => 'John Doe']);
        $this->assertStringContainsString('itemprop="creator"', $string);
        $this->assertStringContainsString('itemtype="https://schema.org/Person"', $string);
        $this->assertStringContainsString('<span itemprop="name">John Doe</span>', $string);
    }

    public function testImageAuthorAttributionStringWithUrl()
    {
        $string = $this->renderComponent([
            'image.authorName' => 'John Doe',
            'image.authorUrl' => 'https://example.com/',
        ]);
        $this->assertStringContainsString('itemprop="creator"', $string);
        $this->assertStringContainsString('itemprop="url"', $string);
        $this->assertStringContainsString('itemtype="https://schema.org/Person"', $string);
        $this->assertStringContainsString('<span itemprop="name">John Doe</span>', $string);
        $this->assertStringContainsString('<a href="https://example.com/"', $string);
    }

    public function testCopyrightString()
    {
        $string = $this->renderComponent(['image.copyright' => 'foo copy']);
        $this->assertStringContainsString('<span itemprop="copyrightNotice">', $string);
        $this->assertStringContainsString('foo copy', $string);
    }

    public function testCopyrightStringInverse()
    {
        $string = $this->renderComponent([]);
        $this->assertStringNotContainsString('<span itemprop="copyrightNotice">', $string);
    }

    public function testLicenseString()
    {
        $string = $this->renderComponent(['image.licenseName' => 'foo']);

        $this->assertStringContainsString('<span itemprop="license">foo</span>', $string);
    }

    public function testLicenseStringWithUrl()
    {
        $image = $this->make([
            'image.licenseName' => 'foo',
            'image.licenseUrl' => 'https://example.com/bar.html',
        ]);
        $string = $this->renderComponent($image);

        $this->assertStringContainsString('<a href="https://example.com/bar.html" rel="license nofollow noopener" itemprop="license">foo</a>', $string);
    }

    public function testLicenseStringInverse()
    {
        $string = $this->renderComponent([]);
        $this->assertStringNotContainsString('<span itemprop="license">', $string);
        $this->assertStringNotContainsString('license', $string);
    }

    public function testLicenseStringInverseWithUrl()
    {
        $string = $this->renderComponent(['image.licenseUrl' => 'https://example.com/bar.html']);
        $this->assertStringNotContainsString('<span itemprop="license">', $string);
        $this->assertStringNotContainsString('license', $string);
    }

    public function testFluentAttributionLogicUsesRichHtmlTags()
    {
        $image = $this->make([
            'image.authorName' => 'John Doe',
            'image.copyright' => 'foo',
            'image.licenseName' => 'foo',
        ]);
        $string = $this->renderComponent($image);

        $this->assertStringContainsString('Image by', $string);
        $this->assertStringContainsString('License', $string);
        $this->assertStringContainsString('<span itemprop="creator" ', $string);
        $this->assertStringContainsString('<span itemprop="copyrightNotice">foo</span>', $string);
        $this->assertStringContainsString('<span itemprop="license">foo</span>', $string);

        $this->assertStringContainsString('Image by', $string);
        $this->assertStringContainsString('John Doe', $string);
    }

    public function testFluentAttributionLogicUsesRichHtmlTags1()
    {
        $image = $this->make(['image.authorName' => 'John Doe']);
        $string = $this->renderComponent($image);
        $this->assertStringContainsString('Image by', $string);
        $this->assertStringContainsString('John Doe', $string);
    }

    public function testFluentAttributionLogicUsesRichHtmlTags2()
    {
        $image = $this->make(['image.copyright' => 'foo']);
        $string = $this->renderComponent($image);

        $this->assertStringContainsString('<span itemprop="copyrightNotice">foo</span>', $string);
    }

    public function testFluentAttributionLogicUsesRichHtmlTags3()
    {
        $image = $this->make(['image.licenseName' => 'foo']);

        $string = $this->renderComponent($image);
        $this->assertStringContainsString('<span itemprop="license">foo</span>', $string);
    }

    public function testFluentAttributionLogicUsesRichHtmlTags4()
    {
        $image = $this->make();
        $string = $this->renderComponent($image);
        $this->assertStringNotContainsString('Image by', $string);
        $this->assertStringNotContainsString('License', $string);
    }

    public function testFluentAttributionLogicCreatesFluentMessages1()
    {
        $image = $this->make([
            'image.authorName' => 'John Doe',
            'image.copyright' => 'CC',
            'image.licenseName' => 'MIT',
        ]);

        $this->assertSame(
            $this->stripWhitespace('Image by John Doe. CC. License MIT.'),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages2()
    {
        $image = $this->make([
            'image.authorName' => 'John Doe',
            'image.licenseName' => 'MIT',
        ]);
        $expect = 'Image by John Doe. License MIT.';
        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages3()
    {
        $expect = 'Image by John Doe. CC.';
        $image = $this->make([
            'image.authorName' => 'John Doe',
            'image.copyright' => 'CC',
        ]);

        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages4()
    {
        $expect = 'All rights reserved.';
        $image = $this->make([
            'image.copyright' => 'All rights reserved',
        ]);

        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages5()
    {
        $expect = 'Image by John Doe.';
        $image = $this->make([
            'image.authorName' => 'John Doe',
        ]);

        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages6()
    {
        $expect = 'License MIT.';
        $image = $this->make([
            'image.licenseName' => 'MIT',
        ]);

        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testFluentAttributionLogicCreatesFluentMessages7()
    {
        $expect = '';
        $image = $this->make();

        $this->assertSame(
            $this->stripWhitespace($expect),
            $this->stripHtml($this->renderComponent($image))
        );
    }

    public function testCacheBusting()
    {
        config(['hyde.cache_busting' => true]);
        $component = $this->renderComponent();
        $this->assertStringContainsString('src="media/foo.jpg?v=98b41d87"', $component);
    }

    public function testImagePathNormalization()
    {
        Hyde::setMediaDirectory('custom_media');
        $this->file('custom_media/bar.png', 'test content');

        $component = $this->renderComponent(['image.source' => 'bar.png']);
        $this->assertStringContainsString('src="custom_media/bar.png"', $component);
    }

    protected function stripHtml(string $string): string
    {
        return trim($this->stripWhitespace(strip_tags($string)), "\t ");
    }

    protected function stripWhitespace(string $string): string
    {
        return str_replace([' ', "\r", "\n"], '', $string);
    }

    protected function renderComponent(FeaturedImage|array $data = ['image.source' => 'foo.jpg']): string
    {
        $image = $data instanceof FeaturedImage ? $data : $this->make($data);

        $page = new MarkdownPost();
        $page->image = $image;
        $this->mockPage($page);

        return view('hyde::components.post.image')->render();
    }

    protected function make(array $data = [], string $path = 'foo.jpg'): FeaturedImage
    {
        $mediaDir = config('hyde.media_directory', '_media');
        $this->file("$mediaDir/$path", 'test content');

        return FeaturedImageFactory::make(FrontMatter::fromArray(array_merge(
            ['image.source' => $path],
            $data,
        )));
    }
}
