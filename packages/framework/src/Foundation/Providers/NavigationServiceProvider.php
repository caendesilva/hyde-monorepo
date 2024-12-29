<?php

declare(strict_types=1);

namespace Hyde\Foundation\Providers;

use Hyde\Foundation\HydeKernel;
use Illuminate\Support\ServiceProvider;
use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;
use Hyde\Framework\Features\Navigation\NavigationMenuGenerator;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->make(HydeKernel::class)->booted(function (): void {
            $this->app->singleton('navigation.main', function (): MainNavigationMenu {
                return NavigationMenuGenerator::handle(MainNavigationMenu::class);
            });

            $this->app->singleton('navigation.sidebar', function (): DocumentationSidebar {
                return NavigationMenuGenerator::handle(DocumentationSidebar::class);
            });
        });
    }
}
