<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Facades;

use Hyde\Facades\Navigation;
use Hyde\Testing\UnitTestCase;
use Hyde\Framework\Features\Navigation\NavigationMenuConfigurationBuilder;

/**
 * @covers \Hyde\Facades\Navigation
 */
class NavigationFacadeTest extends UnitTestCase
{
    public function testItem()
    {
        $item = Navigation::item('home', 'Home', 100);

        $this->assertSame([
            'destination' => 'home',
            'label' => 'Home',
            'priority' => 100,
            'attributes' => [],
        ], $item);
    }

    public function testItemWithOnlyDestination()
    {
        $item = Navigation::item('home');

        $this->assertSame([
            'destination' => 'home',
            'label' => null,
            'priority' => null,
            'attributes' => [],
        ], $item);
    }

    public function testItemWithUrl()
    {
        $item = Navigation::item('https://example.com', 'External', 200);

        $this->assertSame([
            'destination' => 'https://example.com',
            'label' => 'External',
            'priority' => 200,
            'attributes' => [],
        ], $item);
    }

    public function testConfigure()
    {
        $builder = Navigation::configure();

        $this->assertInstanceOf(NavigationMenuConfigurationBuilder::class, $builder);
    }

    public function testConfigureWithChainedMethods()
    {
        $config = Navigation::configure()
            ->setPagePriorities(['index' => 0, 'posts' => 10])
            ->setPageLabels(['index' => 'Home'])
            ->excludePages(['404'])
            ->addNavigationItems([Navigation::item('https://github.com', 'GitHub', 200)])
            ->setSubdirectoryDisplayMode('dropdown')
            ->toArray();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('order', $config);
        $this->assertArrayHasKey('labels', $config);
        $this->assertArrayHasKey('exclude', $config);
        $this->assertArrayHasKey('custom', $config);
        $this->assertArrayHasKey('subdirectory_display', $config);

        $this->assertSame(['index' => 0, 'posts' => 10], $config['order']);
        $this->assertSame(['index' => 'Home'], $config['labels']);
        $this->assertSame(['404'], $config['exclude']);
        $this->assertSame([Navigation::item('https://github.com', 'GitHub', 200)], $config['custom']);
        $this->assertSame('dropdown', $config['subdirectory_display']);
    }

    public function testConfigureWithSomeChainedMethods()
    {
        $config = Navigation::configure()
            ->setPagePriorities(['about' => 1, 'contact' => 2])
            ->setPageLabels(['about' => 'About Us'])
            ->setSubdirectoryDisplayMode('flat')
            ->toArray();

        $this->assertSame(['about' => 1, 'contact' => 2], $config['order']);
        $this->assertSame(['about' => 'About Us'], $config['labels']);
        $this->assertSame('flat', $config['subdirectory_display']);
        $this->assertArrayNotHasKey('exclude', $config);
        $this->assertArrayNotHasKey('custom', $config);
    }
}
