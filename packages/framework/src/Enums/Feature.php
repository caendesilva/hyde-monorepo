<?php

declare(strict_types=1);

namespace Hyde\Enums;

use function defined;
use function constant;

/**
 * A configurable feature that belongs to the Features class.
 *
 * @see \Hyde\Facades\Features
 */
enum Feature
{
    // Page Modules
    case HtmlPages;
    case MarkdownPosts;
    case BladePages;
    case MarkdownPages;
    case DocumentationPages;

    // Frontend Features
    case Darkmode;
    case DocumentationSearch;

    // Integrations
    case Torchlight;

    /** Translates a case name into the corresponding Enum case, if any. If there is no matching case defined, it will return null. */
    public static function fromName(string $name): ?self
    {
        if (! defined("self::$name")) {
            return null;
        }

        return constant("self::$name");
    }
}
