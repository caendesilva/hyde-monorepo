<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Facades\Filesystem;
use Hyde\Support\Models\Route;
use Hyde\Foundation\Facades\Routes;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Framework\Actions\CreatesNewMarkdownPostFile;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\File;

class RelativeLinksAcrossPagesRetainsIntegrityTest extends TestCase
{
    use InteractsWithDirectories;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hyde.cache_busting' => false]);
        config(['hyde.navigation.subdirectory_display' => 'flat']);

        $this->needsDirectory('_pages/nested');
        $this->file('_pages/root.md');
        $this->file('_pages/root1.md');
        Filesystem::touch('_pages/nested/level1.md');
        Filesystem::touch('_pages/nested/level1b.md');

        $this->file('_docs/index.md');
        $this->file('_docs/docs.md');

        (new CreatesNewMarkdownPostFile('My New Post', null, null, null))->save();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(Hyde::path('_pages/nested'));
        Filesystem::unlink('_site/root.html');
        Filesystem::unlink('_site/root1.html');
        $this->resetSite();
        $this->resetPosts();

        parent::tearDown();
    }

    protected function assertSee(string $page, string|array $text): void
    {
        if (is_array($text)) {
            foreach ($text as $string) {
                $this->assertSee($page, $string);
            }

            return;
        }

        $this->assertStringContainsString($text,
            file_get_contents(Hyde::path("_site/$page.html")),
            "Failed asserting that the page '$page' contains the text '$text'");
    }

    public function testRelativeLinksAcrossPagesRetainsIntegrity()
    {
        Routes::getRoutes()->each(function (Route $route): void {
            StaticPageBuilder::handle($route->getPage());
        });

        $this->assertSee('root', [
            '<link rel="stylesheet" href="media/app.css">',
            '<a href="index.html"',
            '<a href="docs/index.html"',
            '<a href="root.html" aria-current="page"',
            '<a href="root1.html"',
            '<a href="nested/level1.html"',
            '<a href="nested/level1b.html"',
        ]);

        $this->assertSee('nested/level1', [
            '<link rel="stylesheet" href="../media/app.css">',
            '<a href="../index.html"',
            '<a href="../docs/index.html"',
            '<a href="../root.html"',
            '<a href="../root1.html"',
            '<a href="../nested/level1.html" aria-current="page"',
            '<a href="../nested/level1b.html"',
        ]);

        $this->assertSee('docs/index', [
            '<link rel="stylesheet" href="../media/app.css">',
            '<a href="../docs/index.html">',
            '<a href="../docs/docs.html"',
            '<a href="../">Back to home page</a>',
        ]);
    }
}
