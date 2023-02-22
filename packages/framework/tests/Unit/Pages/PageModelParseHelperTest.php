<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Pages;

use Hyde\Pages\BladePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Pages\Concerns\HydePage::parse
 */
class PageModelParseHelperTest extends TestCase
{
    public function test_blade_page_get_helper_returns_blade_page_object()
    {
        \Hyde\Facades\Filesystem::touch('_pages/foo.blade.php');

        $object = BladePage::parse('foo');
        $this->assertInstanceOf(BladePage::class, $object);

        \Hyde\Facades\Filesystem::unlink('_pages/foo.blade.php');
    }

    public function test_markdown_page_get_helper_returns_markdown_page_object()
    {
        \Hyde\Facades\Filesystem::touch('_pages/foo.md');

        $object = MarkdownPage::parse('foo');
        $this->assertInstanceOf(MarkdownPage::class, $object);

        \Hyde\Facades\Filesystem::unlink('_pages/foo.md');
    }

    public function test_markdown_post_get_helper_returns_markdown_post_object()
    {
        \Hyde\Facades\Filesystem::touch('_posts/foo.md');

        $object = MarkdownPost::parse('foo');
        $this->assertInstanceOf(MarkdownPost::class, $object);

        \Hyde\Facades\Filesystem::unlink('_posts/foo.md');
    }

    public function test_documentation_page_get_helper_returns_documentation_page_object()
    {
        \Hyde\Facades\Filesystem::touch('_docs/foo.md');

        $object = DocumentationPage::parse('foo');
        $this->assertInstanceOf(DocumentationPage::class, $object);

        \Hyde\Facades\Filesystem::unlink('_docs/foo.md');
    }
}
