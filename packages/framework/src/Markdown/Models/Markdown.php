<?php

declare(strict_types=1);

namespace Hyde\Markdown\Models;

use Hyde\Framework\Services\MarkdownService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * A simple object representation of a Markdown file, with helpful methods to interact with it.
 */
class Markdown implements Arrayable, Stringable, Htmlable
{
    public string $body;

    /**
     * Create a new Markdown object from a string.
     */
    public function __construct(string $body = '')
    {
        $this->body = str_replace("\r\n", "\n", rtrim($body));
    }

    /**
     * Get the source Markdown body.
     */
    public function __toString(): string
    {
        return $this->body;
    }

    /**
     * Get the source Markdown body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Compile the Markdown body to a string of HTML.
     *
     * If the Markdown being compiled is from a page model, supply
     * model's class name here so the dynamic parser can be used.
     *
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>|null  $pageClass
     */
    public function compile(?string $pageClass = null): string
    {
        return static::render($this->body, $pageClass);
    }

    /**
     * Same as Markdown::compile(), but returns an HtmlString object.
     */
    public function toHtml(?string $pageClass = null): HtmlString
    {
        return new HtmlString($this->compile($pageClass));
    }

    /**
     * Get the Markdown document body as an array of lines.
     *
     * @return string[]
     */
    public function toArray(): array
    {
        return explode("\n", $this->body);
    }

    /**
     * Parse a Markdown file into a new Markdown object.
     */
    public static function fromFile(string $path): static
    {
        return MarkdownDocument::parse($path)->markdown();
    }

    /**
     * Render a Markdown string into HTML.
     *
     * If a source page class is provided, that class will be used to configure
     * the Hyde Markdown converter to enable features specific to that page.
     *
     * @return string $html
     */
    public static function render(string $markdown, ?string $pageClass = null): string
    {
        return app(MarkdownService::class, [
            'markdown' => $markdown,
            'pageClass' => $pageClass,
        ])->parse();
    }
}
