<?php

declare(strict_types=1);

namespace Hyde\Testing;

use Illuminate\View\Component;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use ResetsApplication;
    use CreatesTemporaryFiles;
    use InteractsWithPages;
    use FluentTestingHelpers;

    protected static bool $booted = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$booted) {
            $this->resetApplication();

            static::$booted = true;
        }
    }

    protected function tearDown(): void
    {
        $this->cleanUpFilesystem();

        if (method_exists(Component::class, 'flushCache')) {
            /** Until https://github.com/laravel/framework/pull/44648 makes its way into Laravel Zero, we need to clear the view cache ourselves */
            Component::flushCache();
            Component::forgetComponentsResolver();
            Component::forgetFactory();
        }

        parent::tearDown();
    }
}
