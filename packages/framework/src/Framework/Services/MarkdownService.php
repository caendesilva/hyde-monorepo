<?php

declare(strict_types=1);

namespace Hyde\Framework\Services;

use Hyde\Facades\Config;
use Hyde\Facades\Features;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Markdown\Processing\HeadingRenderer;
use Hyde\Framework\Concerns\Internal\SetsUpMarkdownConverter;
use Hyde\Pages\DocumentationPage;
use Hyde\Markdown\MarkdownConverter;
use Hyde\Markdown\Contracts\MarkdownPreProcessorContract as PreProcessor;
use Hyde\Markdown\Contracts\MarkdownPostProcessorContract as PostProcessor;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;

use function str_contains;
use function str_replace;
use function array_merge;
use function array_diff;
use function in_array;
use function implode;
use function explode;
use function substr;
use function strlen;
use function filled;
use function ltrim;
use function trim;

/**
 * Dynamically creates a Markdown converter tailored for the target model and setup,
 * then converts the Markdown to HTML using both pre- and post-processors.
 */
class MarkdownService
{
    use SetsUpMarkdownConverter;

    protected string $markdown;
    protected ?string $pageClass = null;

    protected array $config = [];

    /** @var array<class-string<\League\CommonMark\Extension\ExtensionInterface>> */
    protected array $extensions = [];
    protected MarkdownConverter $converter;

    protected string $html;
    protected array $features = [];

    /** @var array<class-string<\Hyde\Markdown\Contracts\MarkdownPreProcessorContract>> */
    protected array $preprocessors = [];

    /** @var array<class-string<\Hyde\Markdown\Contracts\MarkdownPostProcessorContract>> */
    protected array $postprocessors = [];

    /** @var array<string> Tracks all the headings in the document to ensure identifiers are unique */
    protected array $headingRegistry = [];

    public function __construct(string $markdown, ?string $pageClass = null)
    {
        $this->pageClass = $pageClass;
        $this->markdown = $markdown;
    }

    public function parse(): string
    {
        $this->setupConverter();

        $this->runPreProcessing();

        $this->html = (string) $this->converter->convert($this->markdown);

        $this->runPostProcessing();

        return $this->html;
    }

    protected function setupConverter(): void
    {
        $this->enableDynamicExtensions();

        $this->enableConfigDefinedExtensions();

        $this->mergeMarkdownConfiguration();

        $this->converter = new MarkdownConverter($this->config);

        foreach ($this->extensions as $extension) {
            $this->initializeExtension($extension);
        }

        $this->configureCustomHeadingRenderer();

        $this->registerPreProcessors();
        $this->registerPostProcessors();
    }

    public function addExtension(string $extensionClassName): void
    {
        if (! in_array($extensionClassName, $this->extensions)) {
            $this->extensions[] = $extensionClassName;
        }
    }

    protected function runPreProcessing(): void
    {
        /** @var class-string<PreProcessor> $preprocessor */
        foreach ($this->preprocessors as $preprocessor) {
            $this->markdown = $preprocessor::preprocess($this->markdown);
        }
    }

    protected function runPostProcessing(): void
    {
        if ($this->determineIfTorchlightAttributionShouldBeInjected()) {
            $this->html .= $this->injectTorchlightAttribution();
        }

        /** @var class-string<PostProcessor> $postprocessor */
        foreach ($this->postprocessors as $postprocessor) {
            $this->html = $postprocessor::postprocess($this->html);
        }
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function removeFeature(string $feature): static
    {
        if (in_array($feature, $this->features)) {
            $this->features = array_diff($this->features, [$feature]);
        }

        return $this;
    }

    public function addFeature(string $feature): static
    {
        if (! in_array($feature, $this->features)) {
            $this->features[] = $feature;
        }

        return $this;
    }

    public function isDocumentationPage(): bool
    {
        return isset($this->pageClass) && $this->pageClass === DocumentationPage::class;
    }

    public function withTableOfContents(): static
    {
        $this->addFeature('table-of-contents');

        return $this;
    }

    public function canEnableTorchlight(): bool
    {
        return $this->hasFeature('torchlight') ||
            Features::hasTorchlight();
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features);
    }

    protected function determineIfTorchlightAttributionShouldBeInjected(): bool
    {
        return ! $this->isDocumentationPage()
            && ! (isset($this->pageClass) && $this->pageClass === MarkdownDocument::class)
            && Config::getBool('torchlight.attribution.enabled', true)
            && str_contains($this->html, 'Syntax highlighted by torchlight.dev');
    }

    protected function injectTorchlightAttribution(): string
    {
        return '<br>'.$this->converter->convert(Config::getString(
            'torchlight.attribution.markdown',
            'Syntax highlighted by torchlight.dev'
        ));
    }

    protected function enableAllHtmlElements(): void
    {
        $this->addExtension(DisallowedRawHtmlExtension::class);

        $this->config = array_merge([
            'disallowed_raw_html' => [
                'disallowed_tags' => [],
            ],
        ], $this->config);
    }

    /** Normalize indentation for an un-compiled Markdown string */
    public static function normalizeIndentationLevel(string $string): string
    {
        $lines = self::getNormalizedLines($string);

        [$startNumber, $indentationLevel] = self::findLineContentPositions($lines);

        foreach ($lines as $lineNumber => $line) {
            if ($lineNumber >= $startNumber) {
                $lines[$lineNumber] = substr($line, $indentationLevel);
            }
        }

        return implode("\n", $lines);
    }

    /** @return array<int, string> */
    protected static function getNormalizedLines(string $string): array
    {
        return explode("\n", str_replace(["\t", "\r\n"], ['    ', "\n"], $string));
    }

    /**
     * Find the indentation level and position of the first line that has content.
     *
     * @param  array<int, string>  $lines
     * @return array<int, int>
     */
    protected static function findLineContentPositions(array $lines): array
    {
        foreach ($lines as $lineNumber => $line) {
            if (filled(trim($line))) {
                $lineLen = strlen($line);
                $stripLen = strlen(ltrim($line)); // Length of the line without indentation lets us know its indentation level, and thus how much to strip from each line

                if ($lineLen !== $stripLen) {
                    return [$lineNumber, $lineLen - $stripLen];
                }
            }
        }

        return [0, 0];
    }

    protected function configureCustomHeadingRenderer(): void
    {
        $this->converter->getEnvironment()->addRenderer(Heading::class,
            new HeadingRenderer($this->pageClass, $this->headingRegistry)
        );
    }
}
