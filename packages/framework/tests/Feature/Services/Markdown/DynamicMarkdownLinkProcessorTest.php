<?php

/** @noinspection HtmlUnknownTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Services\Markdown;

use Hyde\Pages\BladePage;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\UnitTestCase;
use Hyde\Support\Models\Route;
use Hyde\Support\Facades\Render;
use Hyde\Foundation\Facades\Routes;
use Hyde\Support\Models\RenderData;
use Hyde\Markdown\Processing\DynamicMarkdownLinkProcessor;

/**
 * @covers \Hyde\Markdown\Processing\DynamicMarkdownLinkProcessor
 *
 * @see \Hyde\Framework\Testing\Feature\DynamicMarkdownLinksFeatureTest
 */
class DynamicMarkdownLinkProcessorTest extends UnitTestCase
{
    protected static bool $needsConfig = true;
    protected static bool $needsKernel = true;

    protected function setUp(): void
    {
        Render::swap(new RenderData());

        Routes::addRoute(new Route(new BladePage('index')));
        Routes::addRoute(new Route(new MarkdownPost('post')));

        // Todo: No way to mock media files, so we are using app.css as a test asset for now.

        self::mockConfig(['hyde.cache_busting' => false]);
    }

    public function testRouteReplacement()
    {
        $input = '<p><a href="_pages/index.blade.php">Home</a></p>';
        $expected = '<p><a href="index.html">Home</a></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testRouteReplacementWithLeadingSlash()
    {
        $input = '<p><a href="/_pages/index.blade.php">Home</a></p>';
        $expected = '<p><a href="index.html">Home</a></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testAssetReplacement()
    {
        $input = '<p><img src="_media/app.css" alt="Logo" /></p>';
        $expected = '<p><img src="media/app.css" alt="Logo" /></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testAssetReplacementWithLeadingSlash()
    {
        $input = '<p><img src="/_media/app.css" alt="Logo" /></p>';
        $expected = '<p><img src="media/app.css" alt="Logo" /></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testMultipleReplacements()
    {
        $input = <<<'HTML'
        <a href="_pages/index.blade.php">Home</a>
        <img src="_media/app.css" alt="Logo" />
        HTML;

        $expected = <<<'HTML'
        <a href="index.html">Home</a>
        <img src="media/app.css" alt="Logo" />
        HTML;

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testNoReplacements()
    {
        $input = '<p>This is a regular <a href="https://example.com">link</a> with no Hyde syntax.</p>';

        $this->assertSame($input, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testNestedRouteReplacement()
    {
        $input = '<p><a href="_posts/post.md">Blog Post</a></p>';
        $expected = '<p><a href="posts/post.html">Blog Post</a></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    // Fault tolerance tests

    public function testNonExistentRouteIsNotReplaced()
    {
        $input = '<p><a href="_pages/non-existent.blade.php">Non-existent Route</a></p>';
        $expected = '<p><a href="_pages/non-existent.blade.php">Non-existent Route</a></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testNonExistentAssetIsNotReplaced()
    {
        $input = '<p><img src="_media/non-existent.png" alt="Non-existent Asset" /></p>';
        $expected = '<p><img src="_media/non-existent.png" alt="Non-existent Asset" /></p>';

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testMixedValidAndInvalidLinks()
    {
        $input = <<<'HTML'
        <a href="_pages/index.blade.php">Valid Home</a>
        <a href="_pages/invalid.blade.php">Invalid Route</a>
        <img src="_media/app.css" alt="Valid Logo" />
        <img src="_media/invalid.jpg" alt="Invalid Asset" />
        HTML;

        $expected = <<<'HTML'
        <a href="index.html">Valid Home</a>
        <a href="_pages/invalid.blade.php">Invalid Route</a>
        <img src="media/app.css" alt="Valid Logo" />
        <img src="_media/invalid.jpg" alt="Invalid Asset" />
        HTML;

        $this->assertSame($expected, DynamicMarkdownLinkProcessor::postprocess($input));
    }

    public function testResetAssetMapCache()
    {
        DynamicMarkdownLinkProcessor::resetAssetMapCache();

        $this->assertTrue(true);
    }
}
