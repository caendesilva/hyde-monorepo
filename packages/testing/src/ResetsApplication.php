<?php

declare(strict_types=1);

namespace Hyde\Testing;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Enums\Feature;

trait ResetsApplication
{
    protected function resetApplication(): void
    {
        $this->resetPages();
        $this->resetPosts();
        $this->resetDocs();
        $this->resetSite();
    }

    protected function resetPages(): void
    {
        array_map('\Hyde\Testing\TestCase::unlinkUnlessDefault', glob(Hyde::path('_pages/*.md')));
        array_map('\Hyde\Testing\TestCase::unlinkUnlessDefault', glob(Hyde::path('_pages/*.blade.php')));
    }

    protected function resetPosts(): void
    {
        array_map('\Hyde\Testing\TestCase::unlinkUnlessDefault', glob(Hyde::path('_posts/*.md')));
    }

    protected function resetDocs(): void
    {
        array_map('\Hyde\Testing\TestCase::unlinkUnlessDefault', glob(Hyde::path('_docs/*.md')));
    }

    protected function resetSite(): void
    {
        Filesystem::cleanDirectory('_site');
    }

    protected function withoutDefaultPages(): void
    {
        Filesystem::unlink('_pages/404.blade.php');
        Filesystem::unlink('_pages/index.blade.php');
    }

    protected function restoreDefaultPages(): void
    {
        copy(Hyde::vendorPath('resources/views/homepages/welcome.blade.php'), Hyde::path('_pages/index.blade.php'));
        copy(Hyde::vendorPath('resources/views/pages/404.blade.php'), Hyde::path('_pages/404.blade.php'));
    }

    /** @experimental We may want to make this a part of {@see static::withoutDefaultPages()} */
    protected function withoutDocumentationSearch(): void
    {
        $features = config('hyde.features');

        $features = array_filter($features, fn (Feature $feature): bool => $feature !== Feature::DocumentationSearch);

        config(['hyde.features' => $features]);
    }

    /** @experimental We may want to make this a part of {@see static::restoreDefaultPages()} */
    protected function restoreDocumentationSearch(): void
    {
        $features = config('hyde.features');

        $features[] = Feature::DocumentationSearch;

        config(['hyde.features' => $features]);
    }

    protected static function unlinkUnlessDefault(string $filepath): void
    {
        $protected = [
            'app.css',
            'index.blade.php',
            '404.blade.php',
            '.gitkeep',
        ];

        if (! in_array(basename($filepath), $protected)) {
            unlink($filepath);
        }
    }
}
