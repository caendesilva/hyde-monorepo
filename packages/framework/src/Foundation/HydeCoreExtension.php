<?php

declare(strict_types=1);

namespace Hyde\Foundation;

use Hyde\Pages\HtmlPage;
use Hyde\Pages\BladePage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Pages\DocumentationPage;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Foundation\Concerns\HydeExtension;
use Hyde\Facades\Features;
use Hyde\Framework\Features\Documentation\DocumentationSearchPage;
use Hyde\Framework\Features\Documentation\DocumentationSearchIndex;

use function array_filter;
use function array_keys;

class HydeCoreExtension extends HydeExtension
{
    /** @return array<class-string<\Hyde\Pages\Concerns\HydePage>> */
    public static function getPageClasses(): array
    {
        return array_keys(array_filter([
            HtmlPage::class => Features::hasHtmlPages(),
            BladePage::class => Features::hasBladePages(),
            MarkdownPage::class => Features::hasMarkdownPages(),
            MarkdownPost::class => Features::hasMarkdownPosts(),
            DocumentationPage::class => Features::hasDocumentationPages(),
        ], fn (bool $value): bool => $value));
    }

    public function discoverPages(PageCollection $collection): void
    {
        if (Features::hasDocumentationSearch()) {
            $collection->addPage(new DocumentationSearchIndex());

            if (DocumentationSearchPage::enabled()) {
                $collection->addPage(new DocumentationSearchPage());
            }
        }
    }
}
