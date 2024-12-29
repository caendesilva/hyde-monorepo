<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Services\MarkdownService;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * @covers \Hyde\Framework\Services\MarkdownService
 * @covers \Hyde\Framework\Concerns\Internal\SetsUpMarkdownConverter
 */
class MarkdownServiceTest extends TestCase
{
    public function testServiceCanParseMarkdownToHtml()
    {
        $markdown = '# Hello World!';

        $html = (new MarkdownService($markdown))->parse();

        $this->assertIsString($html);
        $this->assertSame("<h1>Hello World!</h1>\n", $html);
    }

    public function testServiceCanParseMarkdownToHtmlWithPermalinksDependingOnConfiguration()
    {
        $markdown = '## Hello World!';

        $html = (new MarkdownService($markdown, DocumentationPage::class))->parse();

        $this->assertIsString($html);
        $this->assertStringContainsString('heading-permalink', $html, 'Permalink should be added to documentation pages by default');
        $this->assertSame(
            '<h2 id="hello-world" class="group w-fit scroll-mt-2">Hello World!<a href="#hello-world" class="heading-permalink opacity-0 ml-1 transition-opacity duration-300 ease-linear px-1 group-hover:opacity-100 focus:opacity-100 group-hover:grayscale-0 focus:grayscale-0" title="Permalink">#</a></h2>'."\n", $html
        );

        $html = (new MarkdownService($markdown))->parse();
        $this->assertStringNotContainsString('heading-permalink', $html, 'Permalink should not be be added when the page class is not set');

        $html = (new MarkdownService($markdown, MarkdownPage::class))->parse();
        $this->assertStringNotContainsString('heading-permalink', $html, 'Permalink should not be added to other pages by default');

        Config::set('markdown.permalinks.pages', [MarkdownPage::class]);

        $html = (new MarkdownService($markdown, MarkdownPage::class))->parse();
        $this->assertStringContainsString('heading-permalink', $html, 'Permalink should be added to pages when configured');

        $html = (new MarkdownService($markdown, DocumentationPage::class))->parse();
        $this->assertStringNotContainsString('heading-permalink', $html, 'Permalink should not be added to pages when not configured');

        Config::set('markdown.permalinks.pages', []);

        $html = (new MarkdownService($markdown, DocumentationPage::class))->parse();
        $this->assertStringNotContainsString('heading-permalink', $html, 'Permalinks should not be added to any pages when disabled');

        $html = (new MarkdownService($markdown, MarkdownPage::class))->parse();
        $this->assertStringNotContainsString('heading-permalink', $html, 'Permalinks should not be added to any pages when disabled');
    }

    public function testTorchlightExtensionIsNotEnabledByDefault()
    {
        $markdown = '# Hello World!';
        $service = new MarkdownService($markdown);
        $service->parse();
        $this->assertNotContains('Torchlight\Commonmark\V2\TorchlightExtension', $service->getExtensions());
    }

    public function testTorchlightExtensionIsEnabledAutomaticallyWhenHasTorchlightFeature()
    {
        $markdown = '# Hello World!';
        $service = new MarkdownService($markdown);
        $service->addFeature('torchlight')->parse();
        $this->assertContains('Torchlight\Commonmark\V2\TorchlightExtension', $service->getExtensions());
    }

    public function testTorchlightIntegrationInjectsAttribution()
    {
        $markdown = '# Hello World! <!-- Syntax highlighted by torchlight.dev -->';

        // Enable the extension in config

        $service = new MarkdownService($markdown);

        $html = $service->parse();

        $this->assertStringContainsString('Syntax highlighting by <a href="https://torchlight.dev/" '
                .'rel="noopener nofollow">Torchlight.dev</a>', $html);
    }

    public function testBladedownIsNotEnabledByDefault()
    {
        $service = new MarkdownService('[Blade]: {{ "Hello World!" }}');
        $this->assertSame("<p>[Blade]: {{ &quot;Hello World!&quot; }}</p>\n", $service->parse());
    }

    public function testBladedownCanBeEnabled()
    {
        config(['markdown.enable_blade' => true]);
        $service = new MarkdownService('[Blade]: {{ "Hello World!" }}');
        $service->addFeature('bladedown')->parse();
        $this->assertSame("Hello World!\n", $service->parse());
    }

    public function testRawHtmlTagsAreStrippedByDefault()
    {
        $markdown = '<p>foo</p><style>bar</style><script>hat</script>';
        $service = new MarkdownService($markdown);
        $html = $service->parse();
        $this->assertSame("<p>foo</p>&lt;style>bar&lt;/style>&lt;script>hat&lt;/script>\n", $html);
    }

    public function testRawHtmlTagsAreNotStrippedWhenExplicitlyEnabled()
    {
        config(['markdown.allow_html' => true]);
        $markdown = '<p>foo</p><style>bar</style><script>hat</script>';
        $service = new MarkdownService($markdown);
        $html = $service->parse();
        $this->assertSame("<p>foo</p><style>bar</style><script>hat</script>\n", $html);
    }

    public function testHasFeaturesArray()
    {
        $service = $this->makeService();

        $this->assertIsArray($service->features);
    }

    public function testTheFeaturesArrayIsEmptyByDefault()
    {
        $service = $this->makeService();

        $this->assertEmpty($service->features);
    }

    public function testFeaturesCanBeAddedToTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('test');
        $this->assertContains('test', $service->features);
    }

    public function testFeaturesCanBeRemovedFromTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('test');
        $service->removeFeature('test');
        $this->assertNotContains('test', $service->features);
    }

    public function testMethodChainingCanBeUsedToProgrammaticallyAddFeaturesToTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('test')->addFeature('test2');
        $this->assertContains('test', $service->features);
        $this->assertContains('test2', $service->features);
    }

    public function testMethodChainingCanBeUsedToProgrammaticallyRemoveFeaturesFromTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('test')->addFeature('test2')->removeFeature('test');
        $this->assertNotContains('test', $service->features);
        $this->assertContains('test2', $service->features);
    }

    public function testMethodWithTableOfContentsMethodChainAddsTheTableOfContentsFeature()
    {
        $service = $this->makeService();

        $service->withTableOfContents();
        $this->assertContains('table-of-contents', $service->features);
    }

    public function testHasFeatureReturnsTrueIfTheFeatureIsInTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('test');
        $this->assertTrue($service->hasFeature('test'));
    }

    public function testHasFeatureReturnsFalseIfTheFeatureIsNotInTheArray()
    {
        $service = $this->makeService();

        $this->assertFalse($service->hasFeature('test'));
    }

    public function testMethodCanEnableTorchlightReturnsTrueIfTheTorchlightFeatureIsInTheArray()
    {
        $service = $this->makeService();

        $service->addFeature('torchlight');
        $this->assertTrue($service->canEnableTorchlight());
    }

    public function testMethodCanEnableTorchlightReturnsFalseIfTheTorchlightFeatureIsNotInTheArray()
    {
        $service = $this->makeService();

        $this->assertFalse($service->canEnableTorchlight());
    }

    public function testStripIndentationMethodWithUnindentedMarkdown()
    {
        $service = $this->makeService();

        $markdown = "foo\nbar\nbaz";
        $this->assertSame($markdown, $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithIndentedMarkdown()
    {
        $service = $this->makeService();

        $markdown = "foo\n  bar\n  baz";
        $this->assertSame("foo\nbar\nbaz", $service->normalizeIndentationLevel($markdown));

        $markdown = "  foo\n  bar\n  baz";
        $this->assertSame("foo\nbar\nbaz", $service->normalizeIndentationLevel($markdown));

        $markdown = "    foo\n    bar\n    baz";
        $this->assertSame("foo\nbar\nbaz", $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithTabIndentedMarkdown()
    {
        $service = $this->makeService();

        $markdown = "foo\n\tbar\n\tbaz";
        $this->assertSame("foo\nbar\nbaz", $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithCarriageReturnLineFeed()
    {
        $service = $this->makeService();

        $markdown = "foo\r\n  bar\r\n  baz";
        $this->assertSame("foo\nbar\nbaz", $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithCodeIndentation()
    {
        $service = $this->makeService();

        $markdown = "foo\n    bar\n        baz";
        $this->assertSame("foo\nbar\n    baz", $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithEmptyNewlines()
    {
        $service = $this->makeService();

        $markdown = "foo\n\n  bar\n  baz";
        $this->assertSame("foo\n\nbar\nbaz", $service->normalizeIndentationLevel($markdown));

        $markdown = "foo\n   \n  bar\n  baz";
        $this->assertSame("foo\n   \nbar\nbaz", $service->normalizeIndentationLevel($markdown));
    }

    public function testStripIndentationMethodWithTrailingNewline()
    {
        $service = $this->makeService();

        $markdown = "foo\n  bar\n  baz\n";
        $this->assertSame("foo\nbar\nbaz\n", $service->normalizeIndentationLevel($markdown));
    }

    protected function makeService(): MarkdownServiceTestClass
    {
        return new MarkdownServiceTestClass();
    }
}

class MarkdownServiceTestClass extends MarkdownService
{
    public array $features = [];

    public function __construct(string $markdown = '', ?string $pageClass = null)
    {
        parent::__construct($markdown, $pageClass);
    }
}
