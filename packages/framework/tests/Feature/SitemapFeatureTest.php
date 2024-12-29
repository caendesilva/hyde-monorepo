<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Mockery;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\Filesystem;

/**
 * High level test of the sitemap generation feature.
 *
 * It contains a setup that covers all code paths, proving 100% coverage in actual usage.
 *
 * @see \Hyde\Framework\Testing\Feature\Services\SitemapServiceTest
 * @see \Hyde\Framework\Testing\Feature\Commands\BuildSitemapCommandTest
 *
 * @covers \Hyde\Framework\Features\XmlGenerators\SitemapGenerator
 * @covers \Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap
 * @covers \Hyde\Console\Commands\BuildSitemapCommand
 */
class SitemapFeatureTest extends TestCase
{
    public function testTheSitemapFeature()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $filesystem = Mockery::mock(Filesystem::class)->makePartial();
        $filesystem->shouldReceive('lastModified')->andReturn(Carbon::now()->timestamp);
        File::swap($filesystem);

        $this->cleanUpWhenDone('_site/sitemap.xml');
        $this->setUpBroadSiteStructure();
        $this->withSiteUrl();

        $this->artisan('build:sitemap')
            ->expectsOutputToContain('Created _site/sitemap.xml')
            ->assertExitCode(0);

        $this->assertFileExists('_site/sitemap.xml');

        $this->assertSameXml(
            '<?xml version="1.0" encoding="UTF-8"?>'."\n{$this->stripFormatting($this->expected(Hyde::version()))}\n",
            file_get_contents('_site/sitemap.xml')
        );
    }

    protected function setUpBroadSiteStructure(): void
    {
        $this->file('_pages/about.md', "# About\n\nThis is the about page.");
        $this->file('_pages/contact.html', '<h1>Contact</h1><p>This is the contact page.</p>');
        $this->file('_posts/hello-world.md', "# Hello, World!\n\nThis is the first post.");
        $this->file('_posts/second-post.md', "# Second Post\n\nThis is the second post.");
        $this->file('_docs/index.md', "# Documentation\n\nThis is the documentation index.");
        $this->file('_docs/installation.md', "# Installation\n\nThis is the installation guide.");
        $this->file('_docs/usage.md', "# Usage\n\nThis is the usage guide.");
        $this->file('_docs/404.md', "# 404\n\nThis is the 404 page.");
    }

    protected function expected(string $version): string
    {
        return <<<XML
        <urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9" generator="HydePHP v$version">
            <url>
                <loc>https://example.com/contact.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.75</priority>
            </url>
            <url>
                <loc>https://example.com/404.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.25</priority>
            </url>
            <url>
                <loc>https://example.com/index.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>daily</changefreq>
                <priority>1</priority>
            </url>
            <url>
                <loc>https://example.com/about.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>daily</changefreq>
                <priority>0.9</priority>
            </url>
            <url>
                <loc>https://example.com/posts/hello-world.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.75</priority>
            </url>
            <url>
                <loc>https://example.com/posts/second-post.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.75</priority>
            </url>
            <url>
                <loc>https://example.com/docs/404.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.25</priority>
            </url>
            <url>
                <loc>https://example.com/docs/index.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>daily</changefreq>
                <priority>1</priority>
            </url>
            <url>
                <loc>https://example.com/docs/installation.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>daily</changefreq>
                <priority>0.9</priority>
            </url>
            <url>
                <loc>https://example.com/docs/usage.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>daily</changefreq>
                <priority>0.9</priority>
            </url>
            <url>
                <loc>https://example.com/docs/search.json</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.5</priority>
            </url>
            <url>
                <loc>https://example.com/docs/search.html</loc>
                <lastmod>2024-01-01T12:00:00+00:00</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.5</priority>
            </url>
        </urlset>
        XML;
    }

    protected function stripFormatting(string $xml): string
    {
        return implode('', array_map('trim', explode("\n", $xml)));
    }

    protected function expandLines(string $xml): string
    {
        return str_replace('><', ">\n<", $xml);
    }

    protected function assertSameXml(string $expected, string $actual): void
    {
        $this->assertSame($this->expandLines($expected), $this->expandLines($actual));
    }
}
