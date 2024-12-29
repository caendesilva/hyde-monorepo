<?php

declare(strict_types=1);

namespace Hyde\Framework\Concerns\Internal;

use Hyde\Facades\Config;
use Hyde\Markdown\Processing\BladeDownProcessor;
use Hyde\Markdown\Processing\ShortcodeProcessor;
use Hyde\Markdown\Processing\CodeblockFilepathProcessor;
use Hyde\Markdown\Processing\DynamicMarkdownLinkProcessor;
use Torchlight\Commonmark\V2\TorchlightExtension;

use function array_merge;
use function in_array;

/**
 * Sets up the Markdown converter for the Markdown service.
 *
 * @internal This trait is not covered by the backward compatibility promise.
 *
 * @see \Hyde\Framework\Services\MarkdownService
 */
trait SetsUpMarkdownConverter
{
    protected function enableDynamicExtensions(): void
    {
        if ($this->canEnableTorchlight()) {
            $this->addExtension(TorchlightExtension::class);
        }

        if (Config::getBool('markdown.allow_html', false)) {
            $this->enableAllHtmlElements();
        }
    }

    protected function enableConfigDefinedExtensions(): void
    {
        foreach (Config::getArray('markdown.extensions', []) as $extensionClassName) {
            $this->addExtension($extensionClassName);
        }
    }

    protected function mergeMarkdownConfiguration(): void
    {
        $this->config = array_merge(Config::getArray('markdown.config', []), $this->config);
    }

    public function initializeExtension(string $extensionClassName): void
    {
        $this->converter->getEnvironment()->addExtension(new $extensionClassName());
    }

    protected function registerPreProcessors(): void
    {
        $this->registerPreProcessor(BladeDownProcessor::class, Config::getBool('markdown.enable_blade', false));

        $this->registerPreProcessor(ShortcodeProcessor::class);
        $this->registerPreProcessor(CodeblockFilepathProcessor::class);
    }

    protected function registerPostProcessors(): void
    {
        $this->registerPostProcessor(
            BladeDownProcessor::class,
            Config::getBool('markdown.enable_blade', false)
        );

        $this->registerPostProcessor(
            CodeblockFilepathProcessor::class,
            Config::getBool('markdown.features.codeblock_filepaths', true)
        );

        $this->registerPostProcessor(DynamicMarkdownLinkProcessor::class);
    }

    protected function registerPreProcessor(string $class, bool $when = true): void
    {
        if (! in_array($class, $this->preprocessors) && $when) {
            $this->preprocessors[] = $class;
        }
    }

    protected function registerPostProcessor(string $class, bool $when = true): void
    {
        if (! in_array($class, $this->postprocessors) && $when) {
            $this->postprocessors[] = $class;
        }
    }
}
