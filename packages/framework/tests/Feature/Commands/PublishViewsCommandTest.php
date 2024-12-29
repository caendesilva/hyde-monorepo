<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Commands;

use Hyde\Console\Commands\PublishViewsCommand;
use Hyde\Console\Helpers\ConsoleHelper;
use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Hyde\Console\Commands\PublishViewsCommand
 * @covers \Hyde\Console\Helpers\InteractivePublishCommandHelper
 *
 * @see \Hyde\Framework\Testing\Unit\InteractivePublishCommandHelperTest
 */
class PublishViewsCommandTest extends TestCase
{
    public function testCommandPublishesViews()
    {
        $count = Filesystem::findFiles('vendor/hyde/framework/resources/views/components', '.blade.php', true)->count()
            + Filesystem::findFiles('vendor/hyde/framework/resources/views/layouts', '.blade.php', true)->count();

        $this->artisan('publish:views')
            ->expectsQuestion('Which category do you want to publish?', 'all')
            ->doesntExpectOutputToContain('Selected category')
            ->expectsOutput("Published all $count files to [resources/views/vendor/hyde]")
            ->assertExitCode(0);

        // Assert all groups were published
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde'));
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde/layouts'));
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde/components'));

        // Assert files were published
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/page.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));

        // Assert subdirectories were published with files
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde/components/docs'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/components/docs/documentation-article.blade.php'));
    }

    public function testCanSelectGroupWithArgument()
    {
        ConsoleHelper::disableLaravelPrompts();

        $this->artisan('publish:views layouts')
            ->expectsOutput('Published all [layout] files to [resources/views/vendor/hyde/layouts]')
            ->assertExitCode(0);

        // Assert selected group was published
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde'));
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde/layouts'));

        // Assert files were published
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/page.blade.php'));

        // Assert not selected group was not published
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
    }

    public function testCanSelectGroupWithQuestion()
    {
        ConsoleHelper::disableLaravelPrompts();

        $this->artisan('publish:views')
            ->expectsQuestion('Which category do you want to publish?', '<comment>layouts</comment>: Shared layout views, such as the app layout, navigation menu, and Markdown page templates')
            ->expectsOutput('Published all [layout] files to [resources/views/vendor/hyde/layouts]')
            ->assertExitCode(0);

        // Assert selected group was published
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde'));
        $this->assertDirectoryExists(Hyde::path('resources/views/vendor/hyde/layouts'));

        // Assert files were published
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/page.blade.php'));

        // Assert not selected group was not published
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
    }

    public function testWithInvalidSuppliedTag()
    {
        $this->artisan('publish:views invalid')
            ->expectsOutput("Invalid selection: 'invalid'")
            ->expectsOutput('Allowed values are: [all, layouts, components]')
            ->assertExitCode(1);
    }

    public function testInteractiveSelectionOnWindowsSystemsSkipsInteractiveness()
    {
        ConsoleHelper::mockWindowsOs(true);

        $this->artisan('publish:views components')
            ->expectsOutput('Published all [component] files to [resources/views/vendor/hyde/components]')
            ->assertExitCode(0);
    }

    public function testInteractiveSelectionOnUnixSystems()
    {
        if (windows_os()) {
            $this->markTestSkipped('Test is not applicable on Windows systems.');
        }

        Prompt::fake([
            Key::DOWN, Key::SPACE,
            Key::DOWN, Key::SPACE,
            Key::DOWN, Key::SPACE,
            Key::ENTER,
        ]);

        $output = $this->executePublishViewsCommand();

        Prompt::assertOutputContains('Select the files you want to publish');
        Prompt::assertOutputContains('All files');
        Prompt::assertOutputContains('app.blade.php');
        Prompt::assertOutputContains('docs.blade.php');
        Prompt::assertOutputContains('footer.blade.php');

        $this->assertSame("Published selected [layout] files to [resources/views/vendor/hyde/layouts]\n", $output->fetch());

        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/docs.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/footer.blade.php'));

        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
    }

    public function testInteractiveSelectionWithHittingEnterRightAway()
    {
        if (windows_os()) {
            $this->markTestSkipped('Test is not applicable on Windows systems.');
        }

        Prompt::fake([
            Key::ENTER,
        ]);

        $output = $this->executePublishViewsCommand();

        Prompt::assertOutputContains('Select the files you want to publish');
        Prompt::assertOutputContains('All files');
        Prompt::assertOutputContains('app.blade.php');
        Prompt::assertOutputContains('docs.blade.php');

        $this->assertSame("Published all [layout] files to [resources/views/vendor/hyde/layouts]\n", $output->fetch());

        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/docs.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/footer.blade.php'));

        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
    }

    public function testInteractiveSelectionWithComplexToggles()
    {
        if (windows_os()) {
            $this->markTestSkipped('Test is not applicable on Windows systems.');
        }

        Prompt::fake([
            // Select "all files"
            Key::SPACE,
            // Unselect next file
            Key::DOWN, Key::SPACE,
            // Go back up and deselect the all files option
            Key::UP, Key::SPACE,
            // Select the next three files
            Key::DOWN, Key::SPACE, Key::DOWN, Key::SPACE, Key::DOWN, Key::SPACE,
            // De-select the last file
            Key::SPACE,
            // Confirm selection
            Key::ENTER,
        ]);

        $output = $this->executePublishViewsCommand();

        Prompt::assertOutputContains('Select the files you want to publish');
        Prompt::assertOutputContains('All files');
        Prompt::assertOutputContains('app.blade.php');
        Prompt::assertOutputContains('docs.blade.php');

        $this->assertSame("Published selected [layout] files to [resources/views/vendor/hyde/layouts]\n", $output->fetch());

        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));
        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/docs.blade.php'));
        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/layouts/footer.blade.php'));

        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
    }

    public function testCanSelectGroupWithQuestionAndPrompts()
    {
        if (windows_os()) {
            $this->markTestSkipped('Test is not applicable on Windows systems.');
        }

        $this->artisan('publish:views')
            ->expectsQuestion('Which category do you want to publish?', '<comment>layouts</comment>: Shared layout views, such as the app layout, navigation menu, and Markdown page templates')
            ->expectsOutput('Selected category [layouts]')
            ->expectsQuestion('Select the files you want to publish', (is_dir(Hyde::path('packages')) ? 'packages' : 'vendor/hyde').'/framework/resources/views/layouts/app.blade.php')
            ->expectsOutput('Published selected file to [resources/views/vendor/hyde/layouts/app.blade.php]')
            ->assertExitCode(0);

        $this->assertFileExists(Hyde::path('resources/views/vendor/hyde/layouts/app.blade.php'));

        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/layouts/page.blade.php'));
        $this->assertDirectoryDoesNotExist(Hyde::path('resources/views/vendor/hyde/components'));
        $this->assertFileDoesNotExist(Hyde::path('resources/views/vendor/hyde/components/article-excerpt.blade.php'));
    }

    protected function executePublishViewsCommand(): BufferedOutput
    {
        $command = (new PublishViewsCommand());
        $input = new ArrayInput(['category' => 'layouts'], $command->getDefinition());
        $output = new BufferedOutput();
        $command->setInput($input);
        $command->setOutput(new OutputStyle($input, $output));
        $command->handle();

        return $output;
    }

    protected function tearDown(): void
    {
        ConsoleHelper::clearMocks();
        PromptsReset::resetFallbacks();

        if (File::isDirectory(Hyde::path('resources/views/vendor'))) {
            File::deleteDirectory(Hyde::path('resources/views/vendor'));
        }

        parent::tearDown();
    }
}

abstract class PromptsReset extends Prompt
{
    // Workaround for https://github.com/laravel/prompts/issues/158
    public static function resetFallbacks(): void
    {
        static::$shouldFallback = false;
    }
}
