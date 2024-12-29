<?php

declare(strict_types=1);

namespace Hyde\Markdown\Processing;

use Hyde\Facades\Config;
use Hyde\Markdown\Contracts\MarkdownPostProcessorContract;
use Hyde\Markdown\Contracts\MarkdownPreProcessorContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

use function array_merge;
use function preg_replace;
use function str_contains;
use function str_ireplace;
use function str_starts_with;
use function strtolower;
use function str_replace;
use function explode;
use function implode;
use function sprintf;
use function trim;

/**
 * Resolves file path comments found in Markdown code blocks into a neat badge shown in the top right corner.
 *
 * @todo See about replacing this with a custom Codeblock Blade view that can be customized, even supporting click to copy buttons or arbitrary other features.
 */
class CodeblockFilepathProcessor implements MarkdownPreProcessorContract, MarkdownPostProcessorContract
{
    protected static string $torchlightKey = '<!-- Syntax highlighted by torchlight.dev -->';

    /** @var array<string> */
    protected static array $patterns = [
        '// filepath: ',
        '// filepath ',
        '/* filepath: ',
        '/* filepath ',
        '# filepath: ',
        '# filepath ',
        '<!-- filepath: ',
        '<!-- filepath ',
    ];

    /**
     * Extract lines matching the shortcode pattern and replace them with meta-blocks that will be processed later.
     */
    public static function preprocess(string $markdown): string
    {
        $lines = explode("\n", $markdown);

        foreach ($lines as $index => $line) {
            if (static::lineMatchesPattern($line)) {
                // Add the meta-block two lines before the pattern, placing it just above the code block.
                // This prevents the meta-block from interfering with other processes during compile.
                // We then replace these markers in the post-processor.
                $lines[$index - 2] .= sprintf(
                    "\n<!-- HYDE[Filepath]%s -->",
                    trim(str_ireplace(array_merge(static::$patterns, ['-->']), '', $line))
                );

                // Remove the original comment lines
                unset($lines[$index]);
                // Only unset the next line if it's empty
                if (trim($lines[$index + 1]) === '') {
                    unset($lines[$index + 1]);
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Process the meta-blocks added by the preprocessor, injecting the filepath badge template into the code block.
     */
    public static function postprocess(string $html): string
    {
        $lines = explode("\n", $html);
        $highlightedByTorchlight = str_contains($html, static::$torchlightKey);

        /** @var int $index */
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, '<!-- HYDE[Filepath]')) {
                $lines = static::processFilepathLine($lines, $index, $highlightedByTorchlight);
            }
        }

        return implode("\n", $lines);
    }

    protected static function processFilepathLine(array $lines, int $index, bool $highlightedByTorchlight): array
    {
        $path = static::trimHydeDirective($lines[$index]);
        $label = static::resolveTemplate($path, $highlightedByTorchlight);
        $codeBlockLine = $index + 1;

        unset($lines[$index]);

        $lines[$codeBlockLine] = static::injectLabel($label, $lines[$codeBlockLine], $highlightedByTorchlight);

        return $lines;
    }

    protected static function injectLabel(string $label, string $line, bool $highlightedByTorchlight): string
    {
        return $highlightedByTorchlight
            ? static::injectLabelToTorchlightCodeLine($label, $line)
            : static::injectLabelToCodeLine($label, $line);
    }

    protected static function lineMatchesPattern(string $line): bool
    {
        foreach (static::$patterns as $pattern) {
            if (str_starts_with(strtolower($line), (string) $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected static function trimHydeDirective(string $line): string
    {
        return trim(str_replace('-->', '',
            str_replace('<!-- HYDE[Filepath]', '', $line)
        ));
    }

    protected static function resolveTemplate(string $path, bool $highlightedByTorchlight): string
    {
        return View::make('hyde::components.filepath-label', [
            'path' => Config::getBool('markdown.allow_html', false) ? new HtmlString($path) : $path,
            'highlightedByTorchlight' => $highlightedByTorchlight,
        ])->render();
    }

    protected static function injectLabelToTorchlightCodeLine(string $label, string $lines): string
    {
        return str_replace(
            static::$torchlightKey,
            static::$torchlightKey.$label,
            $lines
        );
    }

    protected static function injectLabelToCodeLine(string $label, string $lines): string
    {
        return preg_replace(
            '/<pre><code class="language-(.*?)">/',
            '<pre><code class="language-$1">'.$label,
            $lines
        );
    }
}
