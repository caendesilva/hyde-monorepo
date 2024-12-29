<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Filesystem;
use Hyde\Support\Includes;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

/**
 * @covers \Hyde\Support\Includes
 *
 * @see \Hyde\Framework\Testing\Unit\IncludesFacadeUnitTest
 */
class IncludesFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory('resources/includes');
    }

    public function testPathReturnsTheIncludesDirectory()
    {
        $this->assertSame(
            Hyde::path('resources/includes'),
            Includes::path()
        );
    }

    public function testPathReturnsAPartialWithinTheIncludesDirectory()
    {
        $this->assertSame(
            Hyde::path('resources/includes/partial.html'),
            Includes::path('partial.html')
        );
    }

    public function testGetReturnsPartial()
    {
        $expected = 'foo bar';
        file_put_contents(Hyde::path('resources/includes/foo.txt'), $expected);
        $this->assertSame($expected, Includes::get('foo.txt'));
        Filesystem::unlink('resources/includes/foo.txt');
    }

    public function testGetReturnsDefaultValueWhenNotFound()
    {
        $this->assertNull(Includes::get('foo.txt'));
        $this->assertSame('default', Includes::get('foo.txt', 'default'));
    }

    public function testHtmlReturnsRenderedPartial()
    {
        $expected = '<h1>foo bar</h1>';
        file_put_contents(Hyde::path('resources/includes/foo.html'), '<h1>foo bar</h1>');
        $this->assertHtmlStringIsSame($expected, Includes::html('foo.html'));
        Filesystem::unlink('resources/includes/foo.html');
    }

    public function testHtmlReturnsEfaultValueWhenNotFound()
    {
        $this->assertNull(Includes::html('foo.html'));
        $this->assertHtmlStringIsSame('<h1>default</h1>', Includes::html('foo.html', '<h1>default</h1>'));
    }

    public function testHtmlWithAndWithoutExtension()
    {
        file_put_contents(Hyde::path('resources/includes/foo.html'), '# foo bar');
        $this->assertHtmlStringIsSame(Includes::html('foo.html'), Includes::html('foo'));
        Filesystem::unlink('resources/includes/foo.html');
    }

    public function testMarkdownReturnsRenderedPartial()
    {
        $expected = '<h1>foo bar</h1>';
        file_put_contents(Hyde::path('resources/includes/foo.md'), '# foo bar');
        $this->assertHtmlStringIsSame($expected, Includes::markdown('foo.md'));
        Filesystem::unlink('resources/includes/foo.md');
    }

    public function testMarkdownReturnsRenderedDefaultValueWhenNotFound()
    {
        $this->assertNull(Includes::markdown('foo.md'));
        $this->assertHtmlStringIsSame('<h1>default</h1>', Includes::markdown('foo.md', '# default'));
    }

    public function testMarkdownWithAndWithoutExtension()
    {
        file_put_contents(Hyde::path('resources/includes/foo.md'), '# foo bar');
        $this->assertHtmlStringIsSame(Includes::markdown('foo.md'), Includes::markdown('foo'));
        Filesystem::unlink('resources/includes/foo.md');
    }

    public function testBladeReturnsRenderedPartial()
    {
        $expected = 'foo bar';
        file_put_contents(Hyde::path('resources/includes/foo.blade.php'), '{{ "foo bar" }}');
        $this->assertHtmlStringIsSame($expected, Includes::blade('foo.blade.php'));
        Filesystem::unlink('resources/includes/foo.blade.php');
    }

    public function testBladeWithAndWithoutExtension()
    {
        file_put_contents(Hyde::path('resources/includes/foo.blade.php'), '# foo bar');
        $this->assertHtmlStringIsSame(Includes::blade('foo.blade.php'), Includes::blade('foo'));
        Filesystem::unlink('resources/includes/foo.blade.php');
    }

    public function testBladeReturnsRenderedDefaultValueWhenNotFound()
    {
        $this->assertNull(Includes::blade('foo.blade.php'));
        $this->assertHtmlStringIsSame('default', Includes::blade('foo.blade.php', '{{ "default" }}'));
    }

    public function testTorchlightAttributionIsNotInjectedToMarkdownPartials()
    {
        $placeholder = 'Syntax highlighted by torchlight.dev';
        $this->file('resources/includes/without-torchlight.md', $placeholder);

        $rendered = Includes::markdown('without-torchlight.md');

        $attribution = 'Syntax highlighting by <a href="https://torchlight.dev/" rel="noopener nofollow">Torchlight.dev</a>';

        $this->assertStringNotContainsString($attribution, $rendered->toHtml());
        $this->assertHtmlStringIsSame("<p>$placeholder</p>", $rendered);
    }

    public function testAdvancedMarkdownDocumentIsCompiledToHtml()
    {
        $markdown = <<<'MARKDOWN'
        # Heading
        
        This is a paragraph. It has some **bold** and *italic* text.
        
        >info Info Blockquote
        
        ```php
        // filepath: hello.php
        echo 'Hello, World!';
        ```
        
        ## Subheading
        
        
        - [x] Checked task list
        - [ ] Unchecked task list
        
        ### Table
        
        | Syntax | Description |
        | ----------- | ----------- |
        | Header | Title |
        | Paragraph | Text |
        
        MARKDOWN;

        $expected = <<<'HTML'
        <h1>Heading</h1>
        <p>This is a paragraph. It has some <strong>bold</strong> and <em>italic</em> text.</p>
        <blockquote class="border-blue-500">
            <p>Info Blockquote</p>
        </blockquote>
        <pre><code class="language-php"><small class="relative float-right opacity-50 hover:opacity-100 transition-opacity duration-250 not-prose hidden md:block top-0 right-0"><span class="sr-only">Filepath: </span>hello.php</small>echo 'Hello, World!';
        </code></pre>
        <h2>Subheading</h2>
        <ul>
        <li><input checked="" disabled="" type="checkbox"> Checked task list</li>
        <li><input disabled="" type="checkbox"> Unchecked task list</li>
        </ul>
        <h3>Table</h3>
        <table>
        <thead>
        <tr>
        <th>Syntax</th>
        <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td>Header</td>
        <td>Title</td>
        </tr>
        <tr>
        <td>Paragraph</td>
        <td>Text</td>
        </tr>
        </tbody>
        </table>
        HTML;

        $this->file('resources/includes/advanced.md', $markdown);
        $this->assertHtmlStringIsSame($expected, Includes::markdown('advanced.md'));
    }

    public function testAdvancedBladePartialIsCompiledToHtml()
    {
        $blade = <<<'BLADE'
        <h1>Heading</h1>
        @foreach(range(1, 3) as $i)
            <p>Paragraph {{ $i }}</p>
        @endforeach
        {{-- This is a comment --}}
        @php($foo = 'bar')
        {{ 'foo ' . $foo }}
        BLADE;

        $expected = <<<'HTML'
        <h1>Heading</h1>
            <p>Paragraph 1</p>
            <p>Paragraph 2</p>
            <p>Paragraph 3</p>
        
        foo bar
        HTML;

        $this->file('resources/includes/advanced.blade.php', $blade);
        $this->assertHtmlStringIsSame($expected, Includes::blade('advanced.blade.php'));
    }

    public function testIncludesUsageFromBladeView()
    {
        // Emulates the actual usage of the Includes facade from a Blade view.

        $this->file('resources/includes/foo.blade.php', '<h1>{{ "Rendered Blade" }}</h1>');
        $this->file('resources/includes/foo.html', '<h1>Literal HTML</h1>');
        $this->file('resources/includes/foo.md', '# Compiled Markdown');

        $view = <<<'BLADE'
        // With extension
        {!! Includes::html('foo.html') !!}
        {!! Includes::blade('foo.blade.php') !!}
        {!! Includes::markdown('foo.md') !!}
        
        // Without extension
        {!! Includes::html('foo') !!}
        {!! Includes::blade('foo') !!}
        {!! Includes::markdown('foo') !!}
        
        // With escaped
        {{ Includes::html('foo.html') }}
        {{ Includes::blade('foo.blade.php') }}
        {{ Includes::markdown('foo.md') }}
        BLADE;

        $expected = <<<'HTML'
        // With extension
        <h1>Literal HTML</h1>
        <h1>Rendered Blade</h1>
        <h1>Compiled Markdown</h1>

        // Without extension
        <h1>Literal HTML</h1>
        <h1>Rendered Blade</h1>
        <h1>Compiled Markdown</h1>

        // With escaped
        <h1>Literal HTML</h1>
        <h1>Rendered Blade</h1>
        <h1>Compiled Markdown</h1>
        HTML;

        $this->assertSame($expected, Blade::render($view));
    }

    protected function assertHtmlStringIsSame(string|HtmlString $expected, mixed $actual): void
    {
        $this->assertInstanceOf(HtmlString::class, $actual);
        $this->assertSame((string) $expected, $actual->toHtml());
    }
}
