<?php

/** @noinspection HtmlUnknownAnchorTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Views;

use Hyde\Framework\Actions\GeneratesTableOfContents;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Actions\GeneratesTableOfContents
 *
 * @see \Hyde\Framework\Testing\Unit\GeneratesSidebarTableOfContentsTest
 */
class SidebarTableOfContentsViewTest extends TestCase
{
    public function testCanGenerateTableOfContents()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        MARKDOWN;

        $result = $this->render($markdown);

        $this->assertIsString($result);

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
                <li>
                    <a href="#level-2">
                        <span>#</span>
                        Level 2
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3">
                                <span>#</span>
                                Level 3
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            HTML, $result
        );
    }

    public function testCanGenerateTableOfContentsForDocumentUsingSetextHeaders()
    {
        $markdown = <<<'MARKDOWN'
        Level 1
        =======
        Level 2
        -------
        Level 2B
        --------
        MARKDOWN;

        $expected = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ## Level 2B
        MARKDOWN;

        $this->assertSame(
            $this->render($expected),
            $this->render($markdown)
        );

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
                <li>
                    <a href="#level-2">
                        <span>#</span>
                        Level 2
                    </a>
                </li>
                <li>
                    <a href="#level-2b">
                        <span>#</span>
                        Level 2B
                    </a>
                </li>
            </ul>
            HTML, $this->render($markdown)
        );
    }

    public function testCanGenerateTableOfContentsWithNonLogicalHeadingOrder()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ### Level 3
        #### Level 4
        ## Level 2
        # Level 1B
        ### Level 3B
        MARKDOWN;

        $result = $this->render($markdown);

        $this->assertIsString($result);

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
              <li>
                <ul>
                  <li>
                    <a href="#level-3">
                      <span>#</span>
                      Level 3
                    </a>
                    <ul>
                      <li>
                        <a href="#level-4">
                          <span>#</span>
                          Level 4
                        </a>
                      </li>
                    </ul>
                  </li>
                </ul>
              </li>
              <li>
                <a href="#level-2">
                  <span>#</span>
                  Level 2
                </a>
              </li>
              <li>
                <ul>
                  <li>
                    <a href="#level-3b">
                      <span>#</span>
                      Level 3B
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
            HTML, $result
        );
    }

    public function testNonHeadingMarkdownIsRemoved()
    {
        $expected = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        MARKDOWN;

        $actual = <<<'MARKDOWN'
        # Level 1
        Foo bar
        ## Level 2
        Bar baz
        ### Level 3
        Baz foo
        MARKDOWN;

        $this->assertSame(
            $this->render($expected),
            $this->render($actual)
        );
    }

    public function testWithNoLevelOneHeading()
    {
        $markdown = <<<'MARKDOWN'
        ## Level 2
        ### Level 3
        MARKDOWN;

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
                <li>
                    <a href="#level-2">
                        <span>#</span>
                        Level 2
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3">
                                <span>#</span>
                                Level 3
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            HTML, $this->render($markdown)
        );
    }

    public function testWithMultipleNestedHeadings()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        ##### Level 5
        ###### Level 6

        ## Level 2B
        ### Level 3B
        ### Level 3C
        ## Level 2C
        ### Level 3D
        MARKDOWN;

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
                <li>
                    <a href="#level-2">
                        <span>#</span>
                        Level 2
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3">
                                <span>#</span>
                                Level 3
                            </a>
                            <ul>
                                <li>
                                    <a href="#level-4">
                                        <span>#</span>
                                        Level 4
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#level-2b">
                        <span>#</span>
                        Level 2B
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3b">
                                <span>#</span>
                                Level 3B
                            </a>
                        </li>
                        <li>
                            <a href="#level-3c">
                                <span>#</span>
                                Level 3C
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#level-2c">
                        <span>#</span>
                        Level 2C
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3d">
                                <span>#</span>
                                Level 3D
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            HTML, $this->render($markdown)
        );
    }

    public function testWithMultipleLevelOneHeadings()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        # Level 1B
        ## Level 2B
        ### Level 3B
        MARKDOWN;

        $this->assertHtmlStructure(<<<'HTML'
            <ul class="table-of-contents">
                <li>
                    <a href="#level-2">
                        <span>#</span>
                        Level 2
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3">
                                <span>#</span>
                                Level 3
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#level-2b">
                        <span>#</span>
                        Level 2B
                    </a>
                    <ul>
                        <li>
                            <a href="#level-3b">
                                <span>#</span>
                                Level 3B
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            HTML, $this->render($markdown)
        );
    }

    public function testWithNoHeadings()
    {
        $this->assertSame('', $this->render("Foo bar\nBaz foo"));
    }

    public function testWithNoContent()
    {
        $this->assertSame('', $this->render(''));
    }

    protected function assertHtmlStructure(string $expected, string $actual): void
    {
        $expected = $this->stripTailwindClasses($expected);

        $this->assertSame(
            $this->normalize($this->reindent($this->removeIndentation(trim($expected)))),
            $this->normalize($this->reindent($this->removeIndentation(trim($actual)))),
        );
    }

    protected function removeIndentation(string $actual): string
    {
        return implode("\n", array_map('trim', explode("\n", $actual)));
    }

    protected function reindent(string $html): string
    {
        // Create a new DOMDocument instance
        $doc = new \DOMDocument();

        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);

        // Load the HTML into DOMDocument, wrapping in a temporary container if needed
        $doc->loadHTML('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Clear any libxml errors
        libxml_clear_errors();

        // Retrieve the body content
        $body = $doc->getElementsByTagName('body')->item(0);

        // If body is empty, return an empty string
        if (! $body) {
            return '';
        }

        // Use a recursive helper function to process child nodes
        return $this->formatNode($body, 0);
    }

    protected function formatNode(\DOMNode $node, int $level): string
    {
        $indent = str_repeat('  ', $level); // Two spaces for each indentation level
        $output = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                // Trim whitespace from text nodes
                $text = trim($child->nodeValue);
                if (! empty($text)) {
                    $output .= $indent.$text."\n";
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                // Open the tag
                $output .= $indent.'<'.$child->nodeName;

                // Add attributes
                if ($child->hasAttributes()) {
                    foreach ($child->attributes as $attr) {
                        $output .= ' '.$attr->nodeName.'="'.htmlspecialchars($attr->nodeValue).'"';
                    }
                }

                $output .= '>';

                // Recursively format children
                if ($child->childNodes->length > 0) {
                    $output .= "\n".$this->formatNode($child, $level + 1);
                    $output .= $indent; // Closing tag at the same indentation
                }

                // Close the tag
                $output .= '</'.$child->nodeName.">\n";
            }
        }

        return $output;
    }

    protected function normalize(string $html): string
    {
        return preg_replace_callback('/<span\b[^>]*>(.*?)<\/span>/s', function ($matches) {
            return '<span>'.trim(preg_replace('/\s+/', ' ', $matches[1])).'</span>';
        }, $html);
    }

    protected function render(string $markdown): string
    {
        $html = view('hyde::components.docs.table-of-contents', [
            'items' => (new GeneratesTableOfContents($markdown))->execute(),
        ])->render();

        return $this->stripTailwindClasses($html);
    }

    protected function stripTailwindClasses(string $html): string
    {
        $replacements = [
            ' pb-3' => '',
            ' class="-ml-8 pl-8 opacity-80 hover:opacity-100 hover:bg-gray-200/20 transition-all duration-300"' => '',
            'class="text-[75%] opacity-50 mr-1 hover:opacity-100 transition-opacity duration-300"' => '',
            ' class="my-0.5"' => '',
            ' class="pl-2"' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }
}
