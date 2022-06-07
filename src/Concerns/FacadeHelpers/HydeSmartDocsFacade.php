<?php

namespace Hyde\Framework\Concerns\FacadeHelpers;

use Hyde\Framework\Helpers\Features;
use Hyde\Framework\Models\DocumentationPage;

/**
 * Provide static facade methods, and instance helpers for HydeSmartDocs.
 *
 * @see \Hyde\Framework\Services\HydeSmartDocs
 */
trait HydeSmartDocsFacade
{
    public static function create(DocumentationPage $page, string $html): static
    {
        return (new static($page, $html))->process();
    }

    public function hasTorchlight(): bool
    {
        return Features::hasTorchlight() && str_contains($this->html, 'Syntax highlighted by torchlight.dev');
    }

    protected function canRenderSourceLink(string $inPosition): bool
    {
        $config = config('docs.edit_source_link_position', 'both');
        $positions = $config === 'both' ? ['header', 'footer'] : [$config];

        return ($this->page->getOnlineSourcePath() !== false) && in_array($inPosition, $positions);
    }
}
