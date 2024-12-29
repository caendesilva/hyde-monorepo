<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Facades\Author;
use Hyde\Facades\Features;
use InvalidArgumentException;
use Hyde\Foundation\Facades\Pages;
use Illuminate\Support\Collection;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\HydeKernel;
use Hyde\Enums\Feature;
use Hyde\Foundation\Kernel\Filesystem;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Framework\HydeServiceProvider;
use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\InMemoryPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\Route;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;

/**
 * This test class runs high-level tests on the HydeKernel class,
 * as most of the logic actually resides in linked service classes.
 *
 * @covers \Hyde\Foundation\HydeKernel
 * @covers \Hyde\Hyde
 *
 * @see \Hyde\Framework\Testing\Unit\HydeHelperFacadeMakeTitleTest
 * @see \Hyde\Framework\Testing\Unit\HydeHelperFacadeMakeSlugTest
 * @see \Hyde\Framework\Testing\Feature\HydeExtensionFeatureTest
 */
class HydeKernelTest extends TestCase
{
    public function testKernelSingletonCanBeAccessedByServiceContainer()
    {
        $this->assertSame(app(HydeKernel::class), app(HydeKernel::class));
    }

    public function testKernelSingletonCanBeAccessedByKernelStaticMethod()
    {
        $this->assertSame(app(HydeKernel::class), HydeKernel::getInstance());
    }

    public function testKernelSingletonCanBeAccessedByHydeFacadeMethod()
    {
        $this->assertSame(app(HydeKernel::class), Hyde::getInstance());
    }

    public function testKernelSingletonCanBeAccessedByHelperFunction()
    {
        $this->assertSame(app(HydeKernel::class), hyde());
    }

    public function testHydeFacadeVersionMethodReturnsKernelVersion()
    {
        $this->assertSame(HydeKernel::version(), Hyde::version());
    }

    public function testHydeFacadeGetFacadeRootMethodReturnsKernelSingleton()
    {
        $this->assertSame(app(HydeKernel::class), Hyde::getFacadeRoot());
        $this->assertSame(HydeKernel::getInstance(), Hyde::getFacadeRoot());
        $this->assertSame(Hyde::getInstance(), Hyde::getFacadeRoot());
    }

    public function testFeaturesHelperReturnsNewFeaturesInstance()
    {
        $this->assertInstanceOf(Features::class, Hyde::features());
    }

    public function testHasFeatureHelperCallsMethodOnFeaturesClass()
    {
        $this->assertSame(Features::has(Feature::BladePages), Hyde::hasFeature(Feature::BladePages));
    }

    public function testCurrentPageHelperReturnsCurrentPageName()
    {
        Render::share('routeKey', 'foo');
        $this->assertSame('foo', Hyde::currentRouteKey());
    }

    public function testCurrentRouteHelperReturnsCurrentRouteObject()
    {
        $expected = new Route(new MarkdownPage());
        Render::share('route', $expected);

        $this->assertInstanceOf(Route::class, Hyde::currentRoute());
        $this->assertSame($expected, Hyde::currentRoute());
    }

    public function testCurrentPageHelperReturnsCurrentPageObject()
    {
        $expected = new MarkdownPage();
        Render::share('page', $expected);

        $this->assertInstanceOf(HydePage::class, Hyde::currentPage());
        $this->assertSame($expected, Hyde::currentPage());
    }

    public function testMakeTitleHelperReturnsTitleFromPageSlug()
    {
        $this->assertSame('Foo Bar', Hyde::makeTitle('foo-bar'));
    }

    public function testMakeSlugHelperReturnsSlugFromTitle()
    {
        $this->assertSame('foo-bar', Hyde::makeSlug('Foo Bar'));
    }

    public function testNormalizeNewlinesReplacesCarriageReturnsWithUnixEndings()
    {
        $this->assertSame("foo\nbar\nbaz", Hyde::normalizeNewlines("foo\nbar\r\nbaz"));
    }

    public function testStripNewlinesHelperRemovesAllNewlines()
    {
        $this->assertSame('foo bar baz', Hyde::stripNewlines("foo\n bar\r\n baz"));
    }

    public function testTrimSlashesFunctionTrimsTrailingSlashes()
    {
        $tests = ['foo',  '/foo',  'foo/',  '/foo/',  '\foo\\',  '\\/foo/\\'];

        foreach ($tests as $test) {
            $this->assertSame('foo', Hyde::trimSlashes($test));
        }

        $tests = ['',  '/',  '\\',  '/\\'];

        foreach ($tests as $test) {
            $this->assertSame('', Hyde::trimSlashes($test));
        }

        $tests = ['foo/bar',  'foo/bar/',  'foo/bar\\',  '\\/foo/bar/\\'];

        foreach ($tests as $test) {
            $this->assertSame('foo/bar', Hyde::trimSlashes($test));
        }
    }

    public function testMarkdownHelperConvertsMarkdownToHtml()
    {
        $this->assertEquals(new HtmlString("<p>foo</p>\n"), Hyde::markdown('foo'));
    }

    public function testMarkdownHelperConvertsIndentedMarkdownToHtml()
    {
        $this->assertEquals(new HtmlString("<p>foo</p>\n"), Hyde::markdown('    foo', true));
    }

    public function testFormatHtmlPathHelperFormatsPathAccordingToConfigRules()
    {
        Config::set('hyde.pretty_urls', false);
        $this->assertSame('foo.html', Hyde::formatLink('foo.html'));
        $this->assertSame('index.html', Hyde::formatLink('index.html'));

        Config::set('hyde.pretty_urls', true);
        $this->assertSame('foo', Hyde::formatLink('foo.html'));
        $this->assertSame('/', Hyde::formatLink('index.html'));
    }

    public function testRelativeLinkHelperReturnsRelativeLinkToDestination()
    {
        Render::share('routeKey', 'bar');
        $this->assertSame('foo', Hyde::relativeLink('foo'));

        Render::share('routeKey', 'foo/bar');
        $this->assertSame('../foo', Hyde::relativeLink('foo'));
    }

    public function testAssetHelperReturnsRelativeLinkToDestination()
    {
        $this->file('_media/foo');

        Render::share('routeKey', 'bar');
        $this->assertSame('media/foo?v=00000000', (string) Hyde::asset('foo'));

        Render::share('routeKey', 'foo/bar');
        $this->assertSame('../media/foo?v=00000000', (string) Hyde::asset('foo'));
    }

    public function testAssetHelperReturnsMediaFileInstanceForGivenName()
    {
        $this->file('_media/foo.jpg');

        $asset = Hyde::asset('foo.jpg');

        $this->assertInstanceOf(MediaFile::class, $asset);
        $this->assertEquals(new MediaFile('_media/foo.jpg'), $asset);

        $this->assertSame('foo.jpg', $asset->getName());
        $this->assertSame('_media/foo.jpg', $asset->getPath());
        $this->assertSame('media/foo.jpg?v=00000000', $asset->getLink());
        $this->assertSame('media/foo.jpg?v=00000000', (string) $asset);
    }

    public function testAssetHelperReturnsAssetPathForGivenName()
    {
        $this->file('_media/foo.jpg');

        Render::share('routeKey', 'foo');
        $this->assertSame('media/foo.jpg?v=00000000', (string) Hyde::asset('foo.jpg'));

        Render::share('routeKey', 'foo/bar');
        $this->assertSame('../media/foo.jpg?v=00000000', (string) Hyde::asset('foo.jpg'));
    }

    public function testAssetHelperTrimsMediaPrefix()
    {
        $this->markTestSkipped('needs to reimplement normalization on the get method');
        $this->file('_media/foo.jpg');

        $this->assertSame('media/foo.jpg?v=00000000', (string) Hyde::asset('media/foo.jpg'));
    }

    public function testAssetHelperSupportsCustomMediaDirectories()
    {
        $this->file('_assets/foo.jpg');

        Hyde::setMediaDirectory('_assets');
        $this->assertSame('assets/foo.jpg?v=00000000', (string) Hyde::asset('foo.jpg'));
    }

    public function testAssetHelperWithoutCacheBusting()
    {
        $this->file('_media/foo.jpg');

        Config::set('hyde.cache_busting', false);

        $this->assertSame('media/foo.jpg', (string) Hyde::asset('foo.jpg'));
    }

    public function testAssetHelperWithoutCacheBustingWithChangedSettingInTheSameLifecycle()
    {
        $this->file('_media/foo.jpg');

        $this->assertSame('media/foo.jpg?v=00000000', (string) Hyde::asset('foo.jpg'));

        Config::set('hyde.cache_busting', false);

        $this->assertSame('media/foo.jpg', (string) Hyde::asset('foo.jpg'));

        Config::set('hyde.cache_busting', true);

        $this->assertSame('media/foo.jpg?v=00000000', (string) Hyde::asset('foo.jpg'));
    }

    public function testAssetHelperWithoutCacheBustingWithChangedSettingInTheSameLifecycleForSameInstance()
    {
        $this->file('_media/foo.jpg');

        $instance = Hyde::asset('foo.jpg');

        $this->assertSame('media/foo.jpg?v=00000000', (string) $instance);

        Config::set('hyde.cache_busting', false);

        $this->assertSame('media/foo.jpg', (string) $instance);

        Config::set('hyde.cache_busting', true);

        $this->assertSame('media/foo.jpg?v=00000000', (string) $instance);
    }

    public function testAssetsHelperGetsAssetsFromKernel()
    {
        $this->assertSame(Hyde::kernel()->assets(), Hyde::assets());
        $this->assertSame(Hyde::asset('app.css'), Hyde::assets()->get('app.css'));
        $this->assertSame(MediaFile::get('app.css'), Hyde::asset('app.css'));
    }

    public function testAssetsHelperGetsAllSiteAssets()
    {
        $this->assertEquals(new Collection([
            'app.css' => new MediaFile('_media/app.css'),
        ]), Hyde::assets());
    }

    public function testAssetsHelperReturnsAssetCollectionSingleton()
    {
        $this->assertSame(Hyde::assets(), Hyde::assets());
    }

    public function testRouteHelper()
    {
        $this->assertNotNull(Hyde::route('index'));
        $this->assertSame(Routes::get('index'), Hyde::route('index'));
    }

    public function testRouteHelperWithInvalidRoute()
    {
        $this->assertNull(Hyde::route('foo'));
    }

    public function testHasSiteUrlHelperReturnsBooleanValueForWhenConfigSettingIsSet()
    {
        Config::set('hyde.url', 'https://example.com');
        $this->assertTrue(Hyde::hasSiteUrl());

        Config::set('hyde.url', null);
        $this->assertFalse(Hyde::hasSiteUrl());
    }

    public function testUrlReturnsQualifiedUrlPaths()
    {
        Config::set('hyde.url', 'https://example.com');
        $this->assertSame('https://example.com', Hyde::url());
        $this->assertSame('https://example.com/foo', Hyde::url('foo'));

        Config::set('hyde.pretty_urls', false);
        $this->assertSame('https://example.com/foo.html', Hyde::url('foo.html'));
        $this->assertSame('https://example.com/index.html', Hyde::url('index.html'));

        Config::set('hyde.pretty_urls', true);
        $this->assertSame('https://example.com/foo', Hyde::url('foo.html'));
        $this->assertSame('https://example.com', Hyde::url('index.html'));
    }

    public function testFilesystemHelperReturnsTheKernelFilesystemInstance()
    {
        $this->assertInstanceOf(Filesystem::class, Hyde::filesystem());
    }

    public function testPathReturnsQualifiedPathForGivenPath()
    {
        $this->assertSame(Hyde::getBasePath(), Hyde::path());
        $this->assertSame(Hyde::getBasePath().'/foo', Hyde::path('foo'));
    }

    public function testVendorPathReturnsQualifiedPathForGivenPath()
    {
        $this->assertSame(Hyde::getBasePath().'/vendor/hyde/framework', Hyde::vendorPath());
        $this->assertSame(Hyde::getBasePath().'/vendor/hyde/framework/foo', Hyde::vendorPath('foo'));
    }

    public function testFluentModelSourcePathHelpers()
    {
        $this->assertSame(Hyde::path('_pages'), BladePage::path());
        $this->assertSame(Hyde::path('_posts'), MarkdownPost::path());
        $this->assertSame(Hyde::path('_pages'), MarkdownPage::path());
        $this->assertSame(Hyde::path('_docs'), DocumentationPage::path());

        $this->assertSame(Hyde::path('_media'), MediaFile::sourcePath());
        $this->assertSame(Hyde::path('_site/media'), MediaFile::outputPath());
        $this->assertSame(Hyde::path('_site'), Hyde::sitePath());
    }

    public function testPathToRelativeHelperReturnsRelativePathForGivenPath()
    {
        $this->assertSame('foo', Hyde::pathToRelative(Hyde::path('foo')));
    }

    public function testToArrayMethod()
    {
        $this->assertSame([
            'version' => Hyde::version(),
            'basePath' => Hyde::getBasePath(),
            'sourceRoot' => Hyde::getSourceRoot(),
            'outputDirectory' => Hyde::getOutputDirectory(),
            'mediaDirectory' => Hyde::getMediaDirectory(),
            'extensions' => Hyde::getRegisteredExtensions(),
            'features' => Hyde::features(),
            'files' => Hyde::files(),
            'pages' => Hyde::pages(),
            'routes' => Hyde::routes(),
            'authors' => Hyde::authors(),
        ], Hyde::toArray());
    }

    public function testJsonSerializeMethod()
    {
        $this->assertSame(Hyde::kernel()->jsonSerialize(), collect(Hyde::toArray())->toArray());
    }

    public function testToJsonMethod()
    {
        $this->assertSame(Hyde::kernel()->toJson(), json_encode(Hyde::toArray()));
    }

    public function testVersionConstantIsAValidSemverString()
    {
        $this->assertMatchesRegularExpression(
            // https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
            '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/',
            HydeKernel::VERSION
        );
    }

    public function testVersionMethodReturnsVersionConstant()
    {
        $this->assertSame(HydeKernel::VERSION, Hyde::version());
    }

    public function testCanGetSourceRoot()
    {
        $this->assertSame('', Hyde::getSourceRoot());
    }

    public function testCanSetSourceRoot()
    {
        Hyde::setSourceRoot('foo');
        $this->assertSame('foo', Hyde::getSourceRoot());
    }

    public function testCanSetSourceRootToAbsoluteProjectPath()
    {
        Hyde::setSourceRoot(Hyde::path('foo'));
        $this->assertSame('foo', Hyde::getSourceRoot());
    }

    public function testSetSourceRootTrimsTrailingSlashes()
    {
        Hyde::setSourceRoot('/foo/');
        $this->assertSame('/foo', Hyde::getSourceRoot());
    }

    public function testCanGetOutputDirectory()
    {
        $this->assertSame('_site', Hyde::getOutputDirectory());
    }

    public function testCanSetOutputDirectory()
    {
        Hyde::setOutputDirectory('foo');

        $this->assertSame('foo', Hyde::getOutputDirectory());
        $this->assertSame(Hyde::path('foo'), Hyde::sitePath());
    }

    public function testCanSetOutputDirectoryToAbsoluteProjectPath()
    {
        Hyde::setOutputDirectory(Hyde::path('foo'));

        $this->assertSame('foo', Hyde::getOutputDirectory());
        $this->assertSame(Hyde::path('foo'), Hyde::sitePath());
    }

    public function testSetOutputDirectoryTrimsTrailingSlashes()
    {
        Hyde::setOutputDirectory('/foo/');
        $this->assertSame('/foo', Hyde::getOutputDirectory());
    }

    public function testSiteOutputDirectoryPathIsNormalizedToTrimTrailingSlashes()
    {
        Hyde::setOutputDirectory('foo/bar/');
        $this->assertSame('foo/bar', Hyde::kernel()->getOutputDirectory());
    }

    public function testCanGetMediaDirectory()
    {
        $this->assertSame('_media', Hyde::getMediaDirectory());
    }

    public function testCanSetMediaDirectory()
    {
        Hyde::setMediaDirectory('foo');
        $this->assertSame('foo', Hyde::getMediaDirectory());
    }

    public function testSetMediaDirectoryTrimsTrailingSlashes()
    {
        Hyde::setMediaDirectory('/foo/');
        $this->assertSame('/foo', Hyde::getMediaDirectory());
    }

    public function testCanGetMediaOutputDirectoryName()
    {
        $this->assertSame('media', Hyde::getMediaOutputDirectory());
    }

    public function testGetMediaOutputDirectoryNameUsesTrimmedVersionOfMediaSourceDirectory()
    {
        Hyde::setMediaDirectory('_foo');
        $this->assertSame('foo', Hyde::getMediaOutputDirectory());
    }

    public function testMediaOutputDirectoryCanBeChangedInConfiguration()
    {
        $this->assertSame('_media', Hyde::getMediaDirectory());

        config(['hyde.media_directory' => '_assets']);
        (new HydeServiceProvider($this->app))->register();

        $this->assertSame('_assets', Hyde::getMediaDirectory());
    }

    public function testCanAccessKernelFluentlyUsingTheFacade()
    {
        $this->assertInstanceOf(HydeKernel::class, Hyde::kernel());
        $this->assertSame(HydeKernel::getInstance(), Hyde::kernel());
    }

    public function testCanAccessKernelSymbolsFluentlyUsingTheFacade()
    {
        $this->assertSame(HydeKernel::VERSION, Hyde::kernel()->version());
    }

    public function testCanRegisterBootingCallbackClosure()
    {
        $kernel = new HydeKernel();

        $kernel->booting(function () {
            $this->assertTrue(true);
        });

        $kernel->boot();
    }

    public function testCanRegisterBootedCallbackClosure()
    {
        $kernel = new HydeKernel();

        $kernel->booted(function () {
            $this->assertTrue(true);
        });

        $kernel->boot();
    }

    public function testCanRegisterBootingCallbackCallable()
    {
        $kernel = new HydeKernel();

        $kernel->booting(new CallableClass($this));

        $kernel->boot();
    }

    public function testCanRegisterBootedCallbackCallable()
    {
        $kernel = new HydeKernel();

        $kernel->booted(new CallableClass($this));

        $kernel->boot();
    }

    public function testBootingCallbackReceivesKernelInstance()
    {
        $kernel = new HydeKernel();

        $kernel->booting(function ($_kernel) use ($kernel) {
            $this->assertSame($kernel, $_kernel);
        });

        $kernel->boot();
    }

    public function testBootedCallbackReceivesKernelInstance()
    {
        $kernel = new HydeKernel();

        $kernel->booted(function ($_kernel) use ($kernel) {
            $this->assertSame($kernel, $_kernel);
        });

        $kernel->boot();
    }

    public function testCanUseBootingCallbacksToInjectCustomPages()
    {
        $kernel = HydeKernel::getInstance();

        $page = new InMemoryPage('foo');

        $kernel->booting(function (HydeKernel $kernel) use ($page): void {
            $kernel->pages()->addPage($page);
        });

        $this->assertSame($page, Pages::getPage('foo'));
        $this->assertSame($page->getRoute(), Routes::getRoute('foo'));
    }

    public function testIsBootedReturnsFalseWhenNotBooted()
    {
        $kernel = new HydeKernel();
        $this->assertFalse($kernel->isBooted());
    }

    public function testIsBootedReturnsTrueWhenBooted()
    {
        $kernel = new HydeKernel();
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }

    public function testIsBootedMethodOnTheFacade()
    {
        $this->assertFalse(Hyde::isBooted());
        Hyde::kernel()->boot();
        $this->assertTrue(Hyde::isBooted());
    }

    public function testFeaturesClassIsBoundAsSingleton()
    {
        $kernel = new HydeKernel();

        $this->assertSame($kernel->features(), $kernel->features());
    }

    public function testAuthors()
    {
        $kernel = new HydeKernel();

        $this->assertInstanceOf(Collection::class, $kernel->authors());
        $this->assertContainsOnlyInstancesOf(PostAuthor::class, $kernel->authors());

        $this->assertSame([
            'mr_hyde' => [
                'username' => 'mr_hyde',
                'name' => 'Mr. Hyde',
                'website' => 'https://hydephp.com',
            ],
        ], $kernel->authors()->toArray());
    }

    public function testAuthorsReturnsSingletonCollection()
    {
        $kernel = new HydeKernel();

        $this->assertSame($kernel->authors(), $kernel->authors());
    }

    public function testAuthorsReturnsEmptyCollectionWhenNoAuthorsDefined()
    {
        $kernel = new HydeKernel();

        Config::set('hyde', []);

        $this->assertInstanceOf(Collection::class, $kernel->authors());
        $this->assertEmpty($kernel->authors());
    }

    public function testAuthorsPropertyIsNotWrittenUntilThereAreAuthorsDefined()
    {
        $kernel = new HydeKernel();

        Config::set('hyde', []);

        $this->assertEmpty($kernel->authors()->toArray());

        Config::set('hyde.authors', ['foo' => Author::create('foo')]);

        $this->assertNotEmpty($kernel->authors()->toArray());
    }

    public function testAuthorsUseTheConfigArrayKeyAsTheUsername()
    {
        Config::set('hyde.authors', ['foo' => Author::create('bar')]);

        $this->assertSame([
            'foo' => [
                'username' => 'foo',
                'name' => 'bar',
            ],
        ], Hyde::authors()->toArray());
    }

    public function testAuthorsThrowsExceptionWhenUsernameIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Author username cannot be empty. Did you forget to set the author\'s array key?');

        Config::set('hyde.authors', ['' => Author::create('foo')]);

        Hyde::authors();
    }
}

class CallableClass
{
    private TestCase $test;

    public function __construct($test)
    {
        $this->test = $test;
    }

    public function __invoke(): void
    {
        $this->test->assertTrue(true);
    }
}
