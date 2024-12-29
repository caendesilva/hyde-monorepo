<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Closure;
use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Kernel\Filesystem;
use Hyde\Framework\Actions\PostBuildTasks\GenerateBuildManifest;
use Hyde\Framework\Actions\PostBuildTasks\GenerateRssFeed;
use Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap as FrameworkGenerateSitemap;
use Hyde\Framework\Features\BuildTasks\BuildTask;
use Hyde\Framework\Features\BuildTasks\PostBuildTask;
use Hyde\Framework\Features\BuildTasks\PreBuildTask;
use Hyde\Framework\Services\BuildTaskService;
use Hyde\Testing\UnitTestCase;
use Illuminate\Console\OutputStyle;
use Mockery;
use ReflectionClass;
use stdClass;
use TypeError;

/**
 * @covers \Hyde\Framework\Services\BuildTaskService
 *
 * @see \Hyde\Framework\Testing\Feature\Services\BuildTaskServiceTest
 */
class BuildTaskServiceUnitTest extends UnitTestCase
{
    protected BuildTaskService $service;

    protected static bool $needsKernel = true;

    protected function setUp(): void
    {
        self::mockConfig(['hyde' => [
            'empty_output_directory' => false,
            'generate_build_manifest' => false,
            'transfer_media_assets' => false,
        ]]);

        $this->createService();
    }

    protected function tearDown(): void
    {
        $this->verifyMockeryExpectations();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(BuildTaskService::class, new BuildTaskService());
    }

    public function testGetTasks()
    {
        $this->assertSame([], $this->service->getRegisteredTasks());
    }

    public function testGetTasksWithTaskRegisteredInConfig()
    {
        self::mockConfig(array_merge(config()->all(), ['hyde.build_tasks' => [TestBuildTask::class]]));
        $this->assertSame([TestBuildTask::class], $this->createService()->getRegisteredTasks());
    }

    public function testRegisterTask()
    {
        $this->service->registerTask(TestBuildTask::class);
        $this->assertSame([TestBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterPreBuildTask()
    {
        $this->service->registerTask(TestPreBuildTask::class);
        $this->assertSame([TestPreBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterPostBuildTask()
    {
        $this->service->registerTask(TestPostBuildTask::class);
        $this->assertSame([TestPostBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterInstantiatedTask()
    {
        $this->service->registerTask(new TestBuildTask());
        $this->assertSame([TestBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterInstantiatedPreBuildTask()
    {
        $this->service->registerTask(new TestPreBuildTask());
        $this->assertSame([TestPreBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterInstantiatedPostBuildTask()
    {
        $this->service->registerTask(new TestPostBuildTask());
        $this->assertSame([TestPostBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterTaskWithInvalidClassTypeThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->service->registerTask(stdClass::class);
    }

    public function testRegisterTaskWithoutChildExtensionThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->service->registerTask(TestBuildTaskNotExtendingChildren::class);
    }

    public function testRegisterTaskWithBaseClassThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->service->registerTask(InstantiableTestBuildTask::class);
    }

    public function testRegisterTaskWithAlreadyRegisteredTask()
    {
        $this->service->registerTask(TestBuildTask::class);
        $this->service->registerTask(TestBuildTask::class);

        $this->assertSame([TestBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testRegisterTaskWithTaskAlreadyRegisteredInConfig()
    {
        self::mockConfig(array_merge(config()->all(), ['hyde.build_tasks' => [TestBuildTask::class]]));
        $this->createService();

        $this->service->registerTask(TestBuildTask::class);
        $this->assertSame([TestBuildTask::class], $this->service->getRegisteredTasks());
    }

    public function testCanRegisterFrameworkTasks()
    {
        $this->service->registerTask(FrameworkGenerateSitemap::class);
        $this->assertSame([FrameworkGenerateSitemap::class], $this->service->getRegisteredTasks());
    }

    public function testCanOverloadFrameworkTasks()
    {
        $this->service->registerTask(FrameworkGenerateSitemap::class);
        $this->service->registerTask(GenerateSitemap::class);

        $this->assertSame([GenerateSitemap::class], $this->service->getRegisteredTasks());
    }

    public function testCanSetOutputWithNull()
    {
        $this->can(fn () => $this->service->setOutput(null));
    }

    public function testCanSetOutputWithOutputStyle()
    {
        $this->can(fn () => $this->service->setOutput($this->mockOutput()));
    }

    public function testGenerateBuildManifestExtendsPostBuildTask()
    {
        $this->assertInstanceOf(PostBuildTask::class, new GenerateBuildManifest());
    }

    public function testGenerateRssFeedExtendsPostBuildTask()
    {
        $this->assertInstanceOf(PostBuildTask::class, new GenerateRssFeed());
    }

    public function testGenerateSitemapExtendsPostBuildTask()
    {
        $this->assertInstanceOf(PostBuildTask::class, new FrameworkGenerateSitemap());
    }

    public function testCanRunPreBuildTasks()
    {
        $this->can(fn () => $this->service->runPreBuildTasks(...));
    }

    public function testCanRunPostBuildTasks()
    {
        $this->can(fn () => $this->service->runPostBuildTasks(...));
    }

    public function testCanRunPreBuildTasksWithTasks()
    {
        $this->can(function () {
            $this->service->registerTask(TestPreBuildTask::class);
            $this->service->runPreBuildTasks();
        });
    }

    public function testCanRunPostBuildTasksWithTasks()
    {
        $this->can(function () {
            $this->service->registerTask(TestPostBuildTask::class);
            $this->service->runPostBuildTasks();
        });
    }

    public function testRunPreBuildTasksCallsHandleMethods()
    {
        $task = $this->setupMock(TestPreBuildTask::class, 'handle')->getMock();

        $this->service->registerTask($task);
        $this->service->runPreBuildTasks();
    }

    public function testRunPostBuildTasksCallsHandleMethods()
    {
        $task = $this->setupMock(TestPostBuildTask::class, 'handle')->getMock();

        $this->service->registerTask($task);
        $this->service->runPostBuildTasks();
    }

    public function testRunPreBuildTasksCallsRunMethods()
    {
        $task = $this->setupMock(TestPreBuildTask::class, 'run')->getMock();

        $this->service->registerTask($task);
        $this->service->runPreBuildTasks();
    }

    public function testRunPostBuildTasksCallsRunMethods()
    {
        $task = $this->setupMock(TestPostBuildTask::class, 'run')->getMock();

        $this->service->registerTask($task);
        $this->service->runPostBuildTasks();
    }

    public function testRunPreBuildTasksCallsRunMethodsWithNullWhenServiceHasNoOutput()
    {
        $task = $this->setupMock(TestPreBuildTask::class, 'run')->with(null)->once()->getMock();

        $this->service->registerTask($task);
        $this->service->runPreBuildTasks();
    }

    public function testRunPostBuildTasksCallsRunMethodsWithNullWhenServiceHasNoOutput()
    {
        $task = $this->setupMock(TestPostBuildTask::class, 'run')->with(null)->once()->getMock();

        $this->service->registerTask($task);
        $this->service->runPostBuildTasks();
    }

    public function testRunPreBuildTasksCallsRunMethodsWithOutputWhenServiceHasOutput()
    {
        $output = $this->mockOutput();
        $task = $this->setupMock(TestPreBuildTask::class, 'run')->with($output)->once()->getMock();

        $this->service->setOutput($output);
        $this->service->registerTask($task);
        $this->service->runPreBuildTasks();
    }

    public function testRunPostBuildTasksCallsRunMethodsWithOutputWhenServiceHasOutput()
    {
        $output = $this->mockOutput();
        $task = $this->setupMock(TestPostBuildTask::class, 'run')->with($output)->once()->getMock();

        $this->service->setOutput($output);
        $this->service->registerTask($task);
        $this->service->runPostBuildTasks();
    }

    public function testServiceSearchesForTasksInAppDirectory()
    {
        $this->mockKernelFilesystem();

        $this->can($this->createService(...));

        $this->assertSame([], $this->service->getRegisteredTasks());

        $this->resetKernelInstance();
    }

    public function testServiceFindsTasksInAppDirectory()
    {
        $files = [
            'app/Actions/GenerateBuildManifestBuildTask.php' => GenerateBuildManifest::class,
            'app/Actions/GenerateRssFeedBuildTask.php' => GenerateRssFeed::class,
        ];

        $this->mockKernelFilesystem($files);

        $this->can($this->createService(...));

        $this->assertSame([
            'Hyde\Framework\Actions\PostBuildTasks\GenerateBuildManifest',
            'Hyde\Framework\Actions\PostBuildTasks\GenerateRssFeed',
        ], $this->service->getRegisteredTasks());

        $this->resetKernelInstance();
    }

    /** Assert that the given closure can be executed */
    protected function can(Closure $ability): void
    {
        $ability();

        $this->assertTrue(true);
    }

    protected function createService(): BuildTaskService
    {
        return tap(new BuildTaskService(), fn (BuildTaskService $service) => $this->service = $service);
    }

    protected function mockKernelFilesystem(array $files = []): void
    {
        $filesystem = $this->setupMock(Filesystem::class, 'smartGlob')
            ->with('app/Actions/*BuildTask.php', 0)
            ->andReturn(collect($files))->getMock();

        // Inject mock into Kernel
        (new ReflectionClass(HydeKernel::getInstance()))->getProperty('filesystem')->setValue(HydeKernel::getInstance(), $filesystem);
    }

    protected function resetKernelInstance(): void
    {
        HydeKernel::setInstance(new HydeKernel());
    }

    protected function setupMock(string $class, string $method): Mockery\ExpectationInterface|Mockery\Expectation|Mockery\HigherOrderMessage
    {
        return Mockery::mock($class)->makePartial()->shouldReceive($method)->once();
    }

    protected function mockOutput(): Mockery\LegacyMockInterface|Mockery\MockInterface|OutputStyle
    {
        return Mockery::mock(OutputStyle::class)->makePartial();
    }
}

class InstantiableTestBuildTask extends BuildTask
{
    use VoidHandleMethod;
}

class TestBuildTask extends PostBuildTask
{
    use VoidHandleMethod;
}

class TestPreBuildTask extends PreBuildTask
{
    use VoidHandleMethod;
}

class TestPostBuildTask extends PostBuildTask
{
    use VoidHandleMethod;
}

class TestBuildTaskNotExtendingChildren extends BuildTask
{
    use VoidHandleMethod;
}

/** Test class to test overloading */
class GenerateSitemap extends FrameworkGenerateSitemap
{
    use VoidHandleMethod;
}

trait VoidHandleMethod
{
    public function handle(): void
    {
        //
    }
}
