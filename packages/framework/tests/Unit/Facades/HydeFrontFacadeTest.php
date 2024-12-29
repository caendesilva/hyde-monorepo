<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Facades;

use Hyde\Hyde;
use Hyde\Facades\HydeFront;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Facades\HydeFront
 */
class HydeFrontFacadeTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    public function testVersionReturnsCorrectHydeFrontVersion()
    {
        if (file_exists(Hyde::path('README.md')) && ! str_contains(file_get_contents(Hyde::path('README.md')), 'HydePHP - Source Code Monorepo')) {
            $this->markTestSkipped('Test skipped when not running in the monorepo.');
        }

        $package = json_decode(file_get_contents(Hyde::path('packages/hydefront/package.json')), true);

        [$major, $minor] = explode('.', $package['version']);

        $this->assertSame("v$major.$minor", HydeFront::version());
    }

    public function testVersionReturnsString()
    {
        $this->assertIsString(HydeFront::version());
    }

    public function testCdnLinkReturnsCorrectUrl()
    {
        $expected = 'https://cdn.jsdelivr.net/npm/hydefront@v3.4/dist/app.css';
        $this->assertSame($expected, HydeFront::cdnLink());
    }

    public function testInjectTailwindConfigReturnsExtractedTailwindConfig()
    {
        $config = HydeFront::injectTailwindConfig();

        $this->assertIsString($config);
        $this->assertStringContainsString("darkMode: 'class'", $config);
        $this->assertStringContainsString('theme: {', $config);
        $this->assertStringContainsString('extend: {', $config);
        $this->assertStringContainsString('typography: {', $config);
        $this->assertStringNotContainsString('plugins', $config);
    }

    public function testInjectTailwindConfigHandlesMissingConfigFileGracefully()
    {
        rename(Hyde::path('tailwind.config.js'), Hyde::path('tailwind.config.js.bak'));
        $this->assertIsString(HydeFront::injectTailwindConfig());
        $this->assertSame('', HydeFront::injectTailwindConfig());
        rename(Hyde::path('tailwind.config.js.bak'), Hyde::path('tailwind.config.js'));
    }
}
