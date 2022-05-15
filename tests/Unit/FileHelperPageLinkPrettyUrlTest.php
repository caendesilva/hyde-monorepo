<?php

namespace Tests\Unit;

use Hyde\Framework\Hyde;
use Tests\TestCase;

/**
 * Class FileHelperPageLinkPrettyUrlTest.
 *
 * @covers \Hyde\Framework\Concerns\Internal\FileHelpers
 */
class FileHelperPageLinkPrettyUrlTest extends TestCase
{
    public function test_helper_returns_string_as_is_if_pretty_urls_is_not_true()
    {
        config(['hyde.prettyUrls' => false]);

        $this->assertEquals('foo/bar.html', Hyde::pageLink('foo/bar.html'));
    }

    public function test_helper_returns_pretty_url_if_pretty_urls_is_true()
    {
        config(['hyde.prettyUrls' => true]);

        $this->assertEquals('foo/bar', Hyde::pageLink('foo/bar.html'));
    }

    public function test_non_pretty_urls_is_default_value_when_config_is_not_set()
    {
        config(['hyde.prettyUrls' => null]);

        $this->assertEquals('foo/bar.html', Hyde::pageLink('foo/bar.html'));
    }

    public function test_helper_respects_absolute_urls()
    {
        config(['hyde.prettyUrls' => false]);
        $this->assertEquals('/foo/bar.html', Hyde::pageLink('/foo/bar.html'));
    }

    public function test_helper_respects_pretty_absolute_urls()
    {
        config(['hyde.prettyUrls' => true]);
        $this->assertEquals('/foo/bar', Hyde::pageLink('/foo/bar.html'));
    }

    public function test_helper_respects_relative_urls()
    {
        config(['hyde.prettyUrls' => false]);
        $this->assertEquals('../foo/bar.html', Hyde::pageLink('../foo/bar.html'));
    }

    public function test_helper_respects_pretty_relative_urls()
    {
        config(['hyde.prettyUrls' => true]);
        $this->assertEquals('../foo/bar', Hyde::pageLink('../foo/bar.html'));
    }

    public function test_non_html_links_are_not_modified()
    {
        config(['hyde.prettyUrls' => true]);
        $this->assertEquals('/foo/bar.jpg', Hyde::pageLink('/foo/bar.jpg'));
    }

    public function test_helper_respects_absolute_urls_with_pretty_urls_enabled()
    {
        config(['hyde.prettyUrls' => true]);
        $this->assertEquals('/foo/bar.jpg', Hyde::pageLink('/foo/bar.jpg'));
    }

    public function test_helper_rewrites_index_when_using_pretty_urls()
    {
        config(['hyde.prettyUrls' => true]);
        $this->assertEquals('/', Hyde::pageLink('index.html'));
    }
}
