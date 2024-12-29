<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Console\Helpers;

use Hyde\Foundation\Providers\ViewServiceProvider;
use Hyde\Framework\Actions\Internal\FileFinder;
use Hyde\Hyde;
use Hyde\Testing\UnitTestCase;
use Hyde\Console\Helpers\ViewPublishGroup;
use Illuminate\Support\Collection;

/**
 * @covers \Hyde\Console\Helpers\ViewPublishGroup
 */
class ViewPublishGroupTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    public static string $packageDirectory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$packageDirectory = is_dir(Hyde::path('packages')) ? 'packages' : 'vendor/hyde';
    }

    protected function setUp(): void
    {
        TestViewPublishGroup::setProvider(TestViewServiceProvider::class);

        app()->bind(FileFinder::class, TestFileFinder::class);
    }

    protected function tearDown(): void
    {
        TestViewPublishGroup::setProvider(ViewServiceProvider::class);

        app()->bind(FileFinder::class, FileFinder::class);
    }

    public function testCanCreateGroup()
    {
        $group = ViewPublishGroup::fromGroup('layouts');

        $this->assertInstanceOf(ViewPublishGroup::class, $group);

        $this->assertSame($group->group, 'layouts');
        $this->assertSame($group->name, 'Layouts');
        $this->assertSame($group->description, "Publish the 'layouts' files for customization.");
        $this->assertSame($group->source, ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts');
        $this->assertSame($group->target, 'resources/views/vendor/hyde/layouts');
        $this->assertSame($group->files, ['app.blade.php', 'page.blade.php', 'post.blade.php']);
    }

    public function testCanCreateGroupWithCustomName()
    {
        $group = ViewPublishGroup::fromGroup('layouts', 'Custom Layouts');

        $this->assertSame($group->name, 'Custom Layouts');
        $this->assertSame($group->description, "Publish the 'layouts' files for customization.");
    }

    public function testCanCreateGroupWithCustomDescription()
    {
        $group = ViewPublishGroup::fromGroup('layouts', null, 'Custom description');

        $this->assertSame($group->name, 'Layouts');
        $this->assertSame($group->description, 'Custom description');
    }

    public function testCanCreateGroupWithCustomNameAndDescription()
    {
        $group = ViewPublishGroup::fromGroup('layouts', 'Custom Layouts', 'Custom description');

        $this->assertSame($group->name, 'Custom Layouts');
        $this->assertSame($group->description, 'Custom description');
    }

    public function testCanGetPublishableFilesMap()
    {
        $group = ViewPublishGroup::fromGroup('layouts');

        $this->assertSame($group->publishableFilesMap(), [
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/app.blade.php' => 'resources/views/vendor/hyde/layouts/app.blade.php',
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/page.blade.php' => 'resources/views/vendor/hyde/layouts/page.blade.php',
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/post.blade.php' => 'resources/views/vendor/hyde/layouts/post.blade.php',
        ]);
    }
}

class TestViewPublishGroup extends ViewPublishGroup
{
    public static function setProvider(string $provider): void
    {
        parent::$provider = $provider;
    }
}

class TestViewServiceProvider extends ViewServiceProvider
{
    public static function pathsToPublish($provider = null, $group = null): array
    {
        ViewPublishGroupTest::assertSame($provider, TestViewServiceProvider::class);
        ViewPublishGroupTest::assertSame($group, 'layouts');

        return [
            Hyde::path(ViewPublishGroupTest::$packageDirectory.'/framework/src/Foundation/Providers/../../../resources/views/layouts') => Hyde::path('resources/views/vendor/hyde/layouts'),
        ];
    }
}

class TestFileFinder extends FileFinder
{
    public static function handle(string $directory, array|string|false $matchExtensions = false, bool $recursive = false): Collection
    {
        ViewPublishGroupTest::assertSame($directory, ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts');
        ViewPublishGroupTest::assertSame($matchExtensions, false);
        ViewPublishGroupTest::assertSame($recursive, true);

        return collect([
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/app.blade.php',
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/page.blade.php',
            ViewPublishGroupTest::$packageDirectory.'/framework/resources/views/layouts/post.blade.php',
        ]);
    }
}
