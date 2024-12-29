<?php

namespace PHPSTORM_META {
    override(\app(0), map([
        'hyde' => \Hyde\Foundation\HydeKernel::class,
        'navigation.main' => \Hyde\Framework\Features\Navigation\MainNavigationMenu::class,
        'navigation.sidebar' => \Hyde\Framework\Features\Navigation\DocumentationSidebar::class,
    ]));
}
