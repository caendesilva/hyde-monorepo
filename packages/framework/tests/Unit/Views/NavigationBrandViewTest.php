<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Views;

use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Testing\TestCase;

/**
 * @see resources/views/components/navigation/navigation-brand.blade.php
 */
class NavigationBrandViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRoute();
        $this->mockPage();
    }

    protected function render(): string
    {
        return view('hyde::components.navigation.navigation-brand', [
            'navigation' => new MainNavigationMenu(),
        ])->render();
    }

    public function testComponentLinksToHomeRoute()
    {
        $this->assertStringContainsString('href="index.html"', $this->render());
    }

    public function testComponentUsesSiteName()
    {
        $this->assertStringContainsString('HydePHP', $this->render());
        config(['hyde.name' => 'foo']);
        $this->assertStringContainsString('foo', $this->render());
    }
}
