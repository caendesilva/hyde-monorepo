<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Foundation\Facades\Pages;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Framework\HydeServiceProvider;
use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\HtmlPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * Feature tests for the StaticPageBuilder class.
 *
 * @covers \Hyde\Framework\Actions\StaticPageBuilder
 */
class StaticPageBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSite();
    }

    protected function tearDown(): void
    {
        $this->resetSite();

        parent::tearDown();
    }

    protected function validateBasicHtml(string $html)
    {
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html lang="en"', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('</title>', $html);
        $this->assertStringContainsString('</head>', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testCanBuildBladePage()
    {
        file_put_contents(BladePage::sourceDirectory().'/foo.blade.php', 'bar');

        $page = new BladePage('foo');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/foo.html'));
        $this->assertStringEqualsFile(Hyde::path('_site/foo.html'), 'bar');

        unlink(BladePage::sourceDirectory().'/foo.blade.php');
        Filesystem::unlink('_site/foo.html');
    }

    public function testCanBuildMarkdownPost()
    {
        $page = MarkdownPost::make('foo', [
            'title' => 'foo',
            'author' => 'bar',
        ], '# Body');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/posts/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('_site/posts/foo.html')));
    }

    public function testCanBuildMarkdownPage()
    {
        $page = MarkdownPage::make('foo', [], '# Body');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('_site/foo.html')));
        Filesystem::unlink('_site/foo.html');
    }

    public function testCanBuildDocumentationPage()
    {
        $page = DocumentationPage::make('foo', [], '# Body');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/'.'docs/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('_site/'.'docs/foo.html')));
    }

    public function testCanBuildHtmlPage()
    {
        $this->file('_pages/foo.html', 'bar');
        $page = new HtmlPage('foo');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/foo.html'));
        $this->assertStringEqualsFile(Hyde::path('_site/foo.html'), 'bar');
        Filesystem::unlink('_site/foo.html');
    }

    public function testCanBuildNestedHtmlPage()
    {
        mkdir(Hyde::path('_pages/foo'));
        file_put_contents(Hyde::path('_pages/foo/bar.html'), 'baz');
        $page = new HtmlPage('foo/bar');

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/foo/bar.html'));
        $this->assertStringEqualsFile(Hyde::path('_site/foo/bar.html'), 'baz');

        Filesystem::unlink('_site/foo/bar.html');
        Filesystem::unlink('_pages/foo/bar.html');
        rmdir(Hyde::path('_pages/foo'));
    }

    public function testCreatesCustomDocumentationDirectory()
    {
        $page = DocumentationPage::make('foo');

        Config::set('hyde.output_directories.documentation-page', 'docs/foo');
        (new HydeServiceProvider($this->app))->register(); // Re-register the service provider to pick up the new config.

        StaticPageBuilder::handle($page);

        $this->assertFileExists(Hyde::path('_site/docs/foo/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('_site/docs/foo/foo.html')));
        Filesystem::unlink('_site/docs/foo/foo.html');
    }

    public function testSiteDirectoryCanBeCustomized()
    {
        Hyde::setOutputDirectory('foo');

        StaticPageBuilder::handle(MarkdownPage::make('foo'));

        $this->assertFileExists(Hyde::path('foo/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('foo/foo.html')));

        File::deleteDirectory(Hyde::path('foo'));
    }

    public function testSiteDirectoryCanBeCustomizedWithNestedPages()
    {
        Hyde::setOutputDirectory('foo');

        StaticPageBuilder::handle(MarkdownPost::make('foo'));

        $this->assertFileExists(Hyde::path('foo/posts/foo.html'));
        $this->validateBasicHtml(file_get_contents(Hyde::path('foo/posts/foo.html')));

        File::deleteDirectory(Hyde::path('foo'));
    }

    public function testCanRebuildBladePage()
    {
        $this->file('_pages/foo.blade.php');
        StaticPageBuilder::handle(Pages::getPage('_pages/foo.blade.php'));

        $this->assertFileExists('_site/foo.html');
        unlink(Hyde::path('_site/foo.html'));
    }

    public function testCanRebuildMarkdownPage()
    {
        $this->file('_pages/foo.md');
        StaticPageBuilder::handle(Pages::getPage('_pages/foo.md'));

        $this->assertFileExists('_site/foo.html');
        unlink(Hyde::path('_site/foo.html'));
    }

    public function testCanRebuildMarkdownPost()
    {
        $this->file('_posts/foo.md');
        StaticPageBuilder::handle(Pages::getPage('_posts/foo.md'));

        $this->assertFileExists('_site/posts/foo.html');
        unlink(Hyde::path('_site/posts/foo.html'));
    }

    public function testCanRebuildDocumentationPage()
    {
        $this->file('_pages/foo.md');
        StaticPageBuilder::handle(Pages::getPage('_pages/foo.md'));

        $this->assertFileExists('_site/foo.html');
        unlink(Hyde::path('_site/foo.html'));
    }
}
