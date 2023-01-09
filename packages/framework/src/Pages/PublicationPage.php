<?php

declare(strict_types=1);

namespace Hyde\Pages;

use Hyde\Framework\Actions\PublicationPageCompiler;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Markdown\Models\Markdown;
use Hyde\Pages\Contracts\DynamicPage;
use Hyde\Publications\Models\PublicationType;
use function str_starts_with;

/**
 * Publication pages adds an easy way to create custom no-code page types,
 * with support using a custom front matter schema and Blade templates.
 *
 * @see \Hyde\Framework\Testing\Feature\PublicationPageTest
 */
class PublicationPage extends Concerns\BaseMarkdownPage implements DynamicPage
{
    public PublicationType $type;

    public static string $sourceDirectory = '';
    public static string $outputDirectory = '';
    public static string $template = '__dynamic';

    public static function make(string $identifier = '', FrontMatter|array $matter = [], string|Markdown $markdown = '', ?PublicationType $type = null): static
    {
        return new static($identifier, $matter, $markdown, $type);
    }

    public function __construct(string $identifier = '', FrontMatter|array $matter = [], Markdown|string $markdown = '', ?PublicationType $type = null)
    {
        $this->type = $type;

        parent::__construct(static::normalizeIdentifier($type->getDirectory(), $identifier), $matter, $markdown);
    }

    public function compile(): string
    {
        return $this->renderComponent();
    }

    protected function renderComponent(): string
    {
        return PublicationPageCompiler::call($this);
    }

    protected static function normalizeIdentifier(string $directory, string $identifier): string
    {
        if (str_starts_with("$identifier/", $directory)) {
            return $identifier;
        }

        return "$directory/$identifier";
    }
}
