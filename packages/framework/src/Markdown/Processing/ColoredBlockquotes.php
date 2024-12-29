<?php

declare(strict_types=1);

namespace Hyde\Markdown\Processing;

use Hyde\Markdown\Contracts\MarkdownShortcodeContract;
use Hyde\Markdown\Models\Markdown;

use function ltrim;
use function explode;
use function view;
use function str_starts_with;
use function trim;

/**
 * @internal This class may be refactored further, thus extending this class is discouraged.
 */
class ColoredBlockquotes implements MarkdownShortcodeContract
{
    /** @var string The core signature. We combine this with an additional check for color later. */
    protected static string $signature = '>';

    /** @var array<string> */
    protected static array $signatures = ['>danger', '>info', '>success', '>warning'];

    public static function signature(): string
    {
        return static::$signature;
    }

    public static function resolve(string $input): string
    {
        return self::stringStartsWithSignature($input)
            ? static::expand($input)
            : $input;
    }

    /**
     * @internal
     *
     * @return array<string>
     */
    public static function getSignatures(): array
    {
        return self::$signatures;
    }

    protected static function expand(string $input): string
    {
        $parts = explode(' ', $input, 2);
        $class = ltrim($parts[0], '>');
        $contents = trim($parts[1] ?? '', ' ');

        return view('hyde::components.colored-blockquote', [
            'class' => $class,
            'contents' => trim(Markdown::render($contents)),
        ])->render();
    }

    protected static function stringStartsWithSignature(string $input): bool
    {
        foreach (static::$signatures as $signature) {
            if (str_starts_with($input, $signature)) {
                return true;
            }
        }

        return false;
    }
}
