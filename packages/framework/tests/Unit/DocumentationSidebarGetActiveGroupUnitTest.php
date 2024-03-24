<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Mockery;
use Illuminate\View\Factory;
use Hyde\Testing\UnitTestCase;
use Hyde\Support\Facades\Render;
use Hyde\Pages\DocumentationPage;
use Hyde\Support\Models\RenderData;
use Illuminate\Support\Facades\View;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Navigation\NavigationGroup;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

/**
 * @covers \Hyde\Framework\Features\Navigation\DocumentationSidebar
 *
 * @see \Hyde\Framework\Testing\Feature\Services\DocumentationSidebarTest
 * @see \Hyde\Framework\Testing\Unit\DocumentationSidebarGetActiveGroupUnitTest
 */
class DocumentationSidebarGetActiveGroupUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    protected RenderData $renderData;

    protected function setUp(): void
    {
        parent::setUp();

        View::swap(Mockery::mock(Factory::class)->makePartial());
        $this->renderData = new RenderData();
        Render::swap($this->renderData);

        self::mockConfig();
    }

    protected function createSidebar(): DocumentationSidebar
    {
        // The sidebar structure
        $items = [
            // Group keys
            'getting-started' => [
                // Group items
                'Introduction',
                'Installation',
            ],
            'configuration' => [
                'Configuration',
                'Environment Variables',
            ],
            'usage' => [
                'Routing',
                'Middleware',
            ],
        ];

        // Create the sidebar items
        foreach ($items as $groupKey => $groupItems) {
            $items[$groupKey] = new NavigationGroup($groupKey, array_map(fn (string $item): NavigationItem => new NavigationItem($item, $item), $groupItems));
        }

        // Create the sidebar
        return new DocumentationSidebar($items);
    }

    public function testNoActiveGroupWhenNoneExists()
    {
        $this->assertNull($this->createSidebar()->getActiveGroup());
    }

    public function testNoActiveGroupWhenOutsideSidebar()
    {
        $this->mockCurrentPageForActiveGroup('foo');
        $this->assertNull($this->createSidebar()->getActiveGroup());
    }

    public function testNoActiveGroupWhenSidebarNotCollapsible()
    {
        self::mockConfig(['docs.sidebar.collapsible' => false]);
        $this->mockCurrentPageForActiveGroup('getting-started');
        $this->assertNull($this->createSidebar()->getActiveGroup());
        self::mockConfig(['docs.sidebar.collapsible' => true]);
    }

    public function testActiveGroupGettingStarted()
    {
        $this->mockCurrentPageForActiveGroup('getting-started');
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupConfiguration()
    {
        $this->mockCurrentPageForActiveGroup('configuration');
        $this->assertSame('configuration', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupUsage()
    {
        $this->mockCurrentPageForActiveGroup('usage');
        $this->assertSame('usage', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupWithIdentifierGettingStarted()
    {
        $this->mockCurrentPageForActiveGroup('getting-started', 'Introduction');
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupWithIdentifierConfiguration()
    {
        $this->mockCurrentPageForActiveGroup('configuration', 'Configuration');
        $this->assertSame('configuration', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupWithIdentifierUsage()
    {
        $this->mockCurrentPageForActiveGroup('usage', 'Routing');
        $this->assertSame('usage', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupDifferentCasingGettingStarted()
    {
        $this->mockCurrentPageForActiveGroup('GETTING-STARTED');
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupDifferentCasingGettingStarted2()
    {
        $this->mockCurrentPageForActiveGroup('Getting-Started');
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testActiveGroupDifferentCasingGettingStarted3()
    {
        $this->mockCurrentPageForActiveGroup('getting-started');
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testGetActiveGroupIsNullWhenNoItemsExist()
    {
        $this->assertNull((new DocumentationSidebar())->getActiveGroup());
    }

    public function testGetActiveGroupIsNullWhenNoGroupsExist()
    {
        $this->assertNull((new DocumentationSidebar([new NavigationItem('foo', 'Foo')]))->getActiveGroup());
    }

    public function testGetActiveGroupIsNullIfTheSetGroupIsNotPresentInTheSidebarItems()
    {
        $this->renderData->setPage(new DocumentationPage('foo', ['navigation.group' => 'foo']));
        $this->assertNull($this->createSidebar()->getActiveGroup());
    }

    public function testGetActiveGroupReturnsFirstGroupWhenRenderingIndexPage()
    {
        $this->renderData->setPage(new DocumentationPage('index'));
        $this->assertSame('getting-started', $this->createSidebar()->getActiveGroup()->getGroupKey());
    }

    public function testGetActiveGroupReturnsFirstGroupByLowestPriorityWhenRenderingIndexPage()
    {
        $sidebar = $this->createSidebar();
        $sidebar->add(new NavigationGroup('other', [new NavigationItem('Other', 'Other')], 0));

        $this->renderData->setPage(new DocumentationPage('index'));
        $this->assertSame('other', $sidebar->getActiveGroup()->getGroupKey());
    }

    public function testGetActiveGroupReturnsExplicitlySetIndexPageGroupWhenRenderingIndexPage()
    {
        $sidebar = $this->createSidebar();

        $this->renderData->setPage(new DocumentationPage('index', ['navigation.group' => 'usage']));
        $this->assertSame('usage', $sidebar->getActiveGroup()->getGroupKey());
    }

    public function testGetActiveGroupReturnsExplicitlySetIndexPageGroupWhenRenderingIndexPageRegardlessOfPriorities()
    {
        $sidebar = $this->createSidebar();
        $sidebar->add(new NavigationGroup('other', [new NavigationItem('Other', 'Other')], 0));

        $this->renderData->setPage(new DocumentationPage('index', ['navigation.group' => 'usage']));
        $this->assertSame('usage', $sidebar->getActiveGroup()->getGroupKey());
    }

    protected function mockCurrentPageForActiveGroup(string $group, string $identifier = 'foo'): void
    {
        $this->renderData->setPage(new DocumentationPage($identifier, ['navigation.group' => $group]));
    }
}