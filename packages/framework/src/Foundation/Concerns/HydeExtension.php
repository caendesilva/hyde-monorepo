<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Kernel\FileCollection;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Foundation\Kernel\RouteCollection;

/**
 * When creating a HydePHP extension, you should create a class that extends this one.
 *
 * After registering your implementation with the HydeKernel (usually in a service provider),
 * Hyde will be able to use the information within to integrate your plugin, and to allow you to
 * hook into various parts of the internal application lifecycle, and through that, all aspects of Hyde.
 *
 * Before creating your extension, it will certainly be helpful if you first become familiar
 * with the basic internal architecture of HydePHP, as well as how the auto-discovery system functions.
 *
 * @link https://hydephp.com/docs/1.x/core-concepts
 *
 * It's important that your class is registered before the HydeKernel boots.
 * An excellent place for this is the 'register' method of your extensions service provider,
 * where you call the 'registerExtension' method of the HydeKernel singleton instance,
 * which you can access via the Hyde\Hyde facade, or via the service container.
 *
 * @example `$this->app->make(HydeKernel::class)->registerExtension(MyExtension::class);`
 */
abstract class HydeExtension
{
    /**
     * If your extension adds new discoverable page classes, you should register them here.
     *
     * Hyde will then automatically discover source files for the new page class,
     * generate routes, and compile the pages during the build process.
     *
     * If your page classes require more complex logic to discover their source files,
     * use the discovery handler methods found below for full process control.
     *
     * @return array<class-string<\Hyde\Pages\Concerns\HydePage>>
     */
    public static function getPageClasses(): array
    {
        return [];
    }

    /**
     * If your extension needs to hook into the file discovery process,
     * you can configure the following handler method. It will be called
     * at the end of the file discovery process. The collection instance
     * will be injected, so that you can interact with it directly.
     */
    public function discoverFiles(FileCollection $collection): void
    {
        //
    }

    /**
     * If your extension needs to hook into the page discovery process,
     * you can configure the following handler method. It will be called
     * at the end of the page discovery process. The collection instance
     * will be injected, so that you can interact with it directly.
     */
    public function discoverPages(PageCollection $collection): void
    {
        //
    }

    /**
     * If your extension needs to hook into the route discovery process,
     * you can configure the following handler method. It will be called
     * at the end of the route discovery process. The collection instance
     * will be injected, so that you can interact with it directly.
     */
    public function discoverRoutes(RouteCollection $collection): void
    {
        //
    }

    /**
     * Register a callback to be run before the kernel is booted,
     * which is before any file/page/route discovery has begun.
     */
    public function booting(HydeKernel $kernel): void
    {
        //
    }

    /**
     * Register a callback to be run after the kernel is booted,
     * which is after file/page/route discovery has completed.
     */
    public function booted(HydeKernel $kernel): void
    {
        //
    }
}
