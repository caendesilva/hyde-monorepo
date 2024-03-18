<?php

declare(strict_types=1);

namespace Hyde\Testing\Support\HtmlTesting;

use DOMElement;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Testing\Assert as PHPUnit;

use function trim;
use function filled;
use function strlen;
use function explode;
use function preg_match;
use function strip_tags;
use function array_filter;

/**
 * A wrapper for an HTML element node, parsed into an assertable and queryable object.
 */
class TestableHtmlElement implements Arrayable
{
    use HtmlTestingAssertions;

    public readonly string $html;
    public readonly string $tag;
    public readonly string $text;
    public readonly ?string $id;

    /** @var array<string> */
    public readonly array $classes;

    /** @var array<array<string, scalar>> */
    public readonly array $attributes;

    /** @var \Illuminate\Support\Collection<\Hyde\Testing\Support\HtmlTesting\TestableHtmlElement> The element's child nodes. */
    public readonly Collection $nodes;

    public readonly DOMElement $element;

    protected ?TestableHtmlElement $parent = null;

    public function __construct(string $html, DOMElement $element, ?TestableHtmlElement $parent = null, ?Collection $nodes = null)
    {
        $this->html = $html;
        $this->element = $element;

        if ($parent) {
            $this->parent = $parent;
        }

        $this->nodes = $nodes ?? new Collection();

        $this->tag = $this->parseTag($html);
        $this->text = $this->parseText($html);
        $this->id = $this->parseId($element);
        $this->classes = $this->parseClasses($element);
        $this->attributes = $this->parseAttributes($element);
    }

    /** @return array{id: ?string, tag: string, text: string, classes: ?array, attributes: ?array, nodes: \Illuminate\Support\Collection<\Hyde\Testing\Support\HtmlTesting\TestableHtmlElement>} */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tag' => $this->tag,
            'text' => $this->text,
            'classes' => $this->classes,
            'attributes' => $this->attributes,
            'nodes' => $this->nodes,
        ], fn ($value): bool => filled($value));
    }

    public function hasId(string $id): static
    {
        return $this->doAssert(fn () => PHPUnit::assertSame($id, $this->id, 'The id attribute did not have the expected value.'));
    }

    public function doesNotHaveId(string $id): static
    {
        return $this->doAssert(fn () => PHPUnit::assertNotSame($id, $this->id, 'The id attribute had the unexpected value.'));
    }

    public function hasClass(string $class): static
    {
        return $this->doAssert(fn () => PHPUnit::assertContains($class, $this->classes, "The class '$class' was not found in the element."));
    }

    public function doesNotHaveClass(string $class): static
    {
        return $this->doAssert(fn () => PHPUnit::assertNotContains($class, $this->classes, "The class '$class' was found in the element."));
    }

    public function hasAttribute(string $attribute, ?string $value = null): static
    {
        if ($attribute === 'id') {
            return $this->hasId($value);
        }

        if ($attribute === 'class') {
            return $this->hasClass($value);
        }

        $this->doAssert(fn () => PHPUnit::assertArrayHasKey($attribute, $this->attributes, "The attribute '$attribute' was not found in the element."));

        if ($value) {
            return $this->doAssert(fn () => PHPUnit::assertSame($value, $this->attributes[$attribute], "The attribute '$attribute' did not have the expected value."));
        }

        return $this;
    }

    public function doesNotHaveAttribute(string $attribute): static
    {
        return $this->doAssert(fn () => PHPUnit::assertArrayNotHasKey($attribute, $this->attributes, "The attribute '$attribute' was found in the element."));
    }

    protected function parseTag(string $html): string
    {
        preg_match('/^<([a-z0-9-]+)/i', $html, $matches);

        return $matches[1] ?? '';
    }

    protected function parseText(string $html): string
    {
        preg_match('/>([^<]+)</', $html, $matches);

        $text = trim(strip_tags($matches[1] ?? ''));

        if (($this->tag === 'style' || $this->tag === 'script') && (strlen($text) > 200)) {
            return "(Inline $this->tag content)";
        }

        return $text;
    }

    protected function parseId(DOMElement $element): ?string
    {
        return $element->getAttribute('id') ?: null;
    }

    protected function parseClasses(DOMElement $element): array
    {
        return array_filter(explode(' ', $element->getAttribute('class')));
    }

    protected function parseAttributes(DOMElement $element): array
    {
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[$attribute->name] = $attribute->value;
        }

        return Arr::except($attributes, ['id', 'class']);
    }
}
