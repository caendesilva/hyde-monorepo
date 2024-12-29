<?php

declare(strict_types=1);

use Hyde\Hyde;
use Hyde\Facades\Asset;
use Hyde\Testing\TestCase;
use Hyde\Foundation\HydeKernel;
use Illuminate\Support\Facades\Blade;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Framework\Exceptions\FileNotFoundException;

/**
 * High level test for the Asset API.
 *
 * @covers \Hyde\Facades\Asset
 * @covers \Hyde\Support\Filesystem\MediaFile
 * @covers \Hyde\Foundation\Kernel\Hyperlinks
 */
class AssetAPIFeatureTest extends TestCase
{
    public function testAssetAPIExamples()
    {
        // Tests for the Asset API, can be used to try out the PhpStorm autocompletion

        $conditions = [
            'existsTrue' => \Hyde\Facades\Asset::exists('app.css'),
            'existsFalse' => \Hyde\Facades\Asset::exists('missing.png'),
            'getters' => [
                (string) \Hyde\Facades\Asset::get('app.css'),
                \Hyde\Facades\Asset::get('app.css'),
                \Hyde::asset('app.css'),
                \Hyde\Hyde::asset('app.css'),
                Hyde::kernel()->asset('app.css'),
                HydeKernel::getInstance()->asset('app.css'),
                hyde()->asset('app.css'),
                asset('app.css'),
            ],
            'accessors' => [
                new MediaFile('app.css'),
                MediaFile::make('app.css'),
                MediaFile::get('app.css'),
                MediaFile::sourcePath('app.css'),
                MediaFile::outputPath('app.css'),
            ],
        ];

        $this->assertEquals([
            'existsTrue' => true,
            'existsFalse' => false,
            'getters' => [
                "media/app.css?v={$this->getAppStylesVersion()}",
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
                MediaFile::get('app.css'),
            ],
            'accessors' => [
                new MediaFile('app.css'),
                new MediaFile('app.css'),
                MediaFile::get('app.css'),
                Hyde::path('_media/app.css'),
                Hyde::sitePath('media/app.css'),
            ],
        ], $conditions);
    }

    public function testAssetAPIUsagesInBladeViews()
    {
        $view = /** @lang Blade */ <<<'Blade'
        @if(Asset::exists('app.css'))
            <link rel="stylesheet" href="{{ Asset::get('app.css') }}">
            <link rel="stylesheet" href="{{ Hyde::asset('app.css') }}">
            <link rel="stylesheet" href="{{ asset('app.css') }}">
        @endif
        
        @if(Asset::exists('missing.png'))
            Found missing.png
        @else
            Missing missing.png
        @endif
        Blade;

        $html = Blade::render($view);

        $this->assertSame(<<<HTML
        <link rel="stylesheet" href="media/app.css?v={$this->getAppStylesVersion()}">
            <link rel="stylesheet" href="media/app.css?v={$this->getAppStylesVersion()}">
            <link rel="stylesheet" href="media/app.css?v={$this->getAppStylesVersion()}">
        
            Missing missing.png

        HTML, $html);
    }

    public function testThrowsExceptionForNonExistentMediaFile()
    {
        $nonExistentFile = 'non_existent_file.txt';

        $accessors = [
            fn () => \Hyde\Facades\Asset::get($nonExistentFile),
            fn () => \Hyde::asset($nonExistentFile),
            fn () => \Hyde\Hyde::asset($nonExistentFile),
            fn () => Hyde::kernel()->asset($nonExistentFile),
            fn () => HydeKernel::getInstance()->asset($nonExistentFile),
            fn () => hyde()->asset($nonExistentFile),
            fn () => asset($nonExistentFile),
            fn () => new MediaFile($nonExistentFile),
            fn () => MediaFile::make($nonExistentFile),
            fn () => MediaFile::get($nonExistentFile),
        ];

        foreach ($accessors as $test => $accessor) {
            try {
                $accessor();
                $this->fail('Expected exception to be thrown for syntax test #'.$test);
            } catch (FileNotFoundException $exception) {
                $this->assertSame('File [_media/non_existent_file.txt] not found when trying to resolve a media asset.', $exception->getMessage());
            }
        }

        $this->assertSame(Hyde::path('_media/non_existent_file.txt'), MediaFile::sourcePath($nonExistentFile));
        $this->assertSame(Hyde::path('_site/media/non_existent_file.txt'), MediaFile::outputPath($nonExistentFile));
    }

    public function testAssetSupportsCustomMediaDirectories()
    {
        Hyde::setMediaDirectory('_assets');

        $this->directory('_assets');
        $this->file('_assets/app.css');

        $path = (string) Asset::get('app.css');

        $this->assertIsString($path);
        $this->assertSame('assets/app.css?v='.hash_file('crc32', Hyde::path('_assets/app.css')), $path);
    }

    protected function getAppStylesVersion(): string
    {
        return MediaFile::get('app.css')->getHash();
    }
}
