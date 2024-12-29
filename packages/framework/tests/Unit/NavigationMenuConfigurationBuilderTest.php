<?php

declare(strict_types=1);

use Hyde\Testing\UnitTestCase;
use Illuminate\Contracts\Support\Arrayable;
use Hyde\Framework\Features\Navigation\NavigationMenuConfigurationBuilder;
use Hyde\Facades\Navigation;

/**
 * @covers \Hyde\Framework\Features\Navigation\NavigationMenuConfigurationBuilder
 */
class NavigationMenuConfigurationBuilderTest extends UnitTestCase
{
    private NavigationMenuConfigurationBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new NavigationMenuConfigurationBuilder();
    }

    public function testSetPagePriorities()
    {
        $order = ['index' => 0, 'posts' => 10, 'docs/index' => 100];
        $result = $this->builder->setPagePriorities($order)->toArray();

        $this->assertArrayHasKey('order', $result);
        $this->assertSame($order, $result['order']);
    }

    public function testSetPageLabels()
    {
        $labels = ['index' => 'Home', 'docs/index' => 'Docs'];
        $result = $this->builder->setPageLabels($labels)->toArray();

        $this->assertArrayHasKey('labels', $result);
        $this->assertSame($labels, $result['labels']);
    }

    public function testExcludePages()
    {
        $exclude = ['404', 'admin'];
        $result = $this->builder->excludePages($exclude)->toArray();

        $this->assertArrayHasKey('exclude', $result);
        $this->assertSame($exclude, $result['exclude']);
    }

    public function testAddNavigationItems()
    {
        $custom = [
            Navigation::item('https://example.com', 'Example', 200),
            Navigation::item('https://github.com', 'GitHub', 300),
        ];

        $result = $this->builder->addNavigationItems($custom)->toArray();

        $this->assertArrayHasKey('custom', $result);
        $this->assertSame($custom, $result['custom']);
    }

    public function testSetSubdirectoryDisplayMode()
    {
        $displayModes = ['dropdown', 'flat', 'hidden'];

        foreach ($displayModes as $mode) {
            $result = $this->builder->setSubdirectoryDisplayMode($mode)->toArray();

            $this->assertArrayHasKey('subdirectory_display', $result);
            $this->assertSame($mode, $result['subdirectory_display']);
        }
    }

    public function testChainedMethods()
    {
        $result = $this->builder
            ->setPagePriorities(['index' => 0, 'posts' => 10])
            ->setPageLabels(['index' => 'Home'])
            ->excludePages(['404'])
            ->addNavigationItems([Navigation::item('https://github.com', 'GitHub', 200)])
            ->setSubdirectoryDisplayMode('dropdown')
            ->toArray();

        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('exclude', $result);
        $this->assertArrayHasKey('custom', $result);
        $this->assertArrayHasKey('subdirectory_display', $result);
    }

    public function testEmptyConfiguration()
    {
        $result = $this->builder->toArray();

        $this->assertEmpty($result);
    }

    public function testInvalidSubdirectoryDisplay()
    {
        $this->expectException(\TypeError::class);
        $this->builder->setSubdirectoryDisplayMode('invalid');
    }

    public function testRealLifeUsageScenario()
    {
        $result = $this->builder
            ->setPagePriorities([
                'index' => 0,
                'posts' => 10,
                'docs/index' => 100,
            ])
            ->setPageLabels([
                'index' => 'Home',
                'docs/index' => 'Docs',
            ])
            ->excludePages([
                '404',
            ])
            ->addNavigationItems([
                Navigation::item('https://github.com/hydephp/hyde', 'GitHub', 200),
            ])
            ->setSubdirectoryDisplayMode('dropdown')
            ->toArray();

        $this->assertSame([
            'order' => ['index' => 0, 'posts' => 10, 'docs/index' => 100],
            'labels' => ['index' => 'Home', 'docs/index' => 'Docs'],
            'exclude' => ['404'],
            'custom' => [
                ['destination' => 'https://github.com/hydephp/hyde', 'label' => 'GitHub', 'priority' => 200, 'attributes' => []],
            ],
            'subdirectory_display' => 'dropdown',
        ], $result);
    }

    public function testHideSubdirectoriesFromNavigationShorthand()
    {
        $result = $this->builder->hideSubdirectoriesFromNavigation()->toArray();

        $this->assertSame('hidden', $result['subdirectory_display']);
    }

    public function testArrayableInterface()
    {
        $this->assertInstanceOf(Arrayable::class, $this->builder);
    }

    public function testArrayObjectBehavior()
    {
        $this->builder->setPagePriorities(['index' => 0]);

        $this->assertCount(1, $this->builder);
        $this->assertSame(['order' => ['index' => 0]], $this->builder->getArrayCopy());
    }

    public function testToArrayMethodReturnsSameResultAsArrayObject()
    {
        $this->builder->setPagePriorities(['index' => 0]);

        $this->assertSame(['order' => ['index' => 0]], $this->builder->toArray());
    }
}
