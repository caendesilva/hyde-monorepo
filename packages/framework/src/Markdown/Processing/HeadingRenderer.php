<?php

declare(strict_types=1);

namespace Hyde\Markdown\Processing;

use Hyde\Pages\DocumentationPage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Renders a heading node, and supports built-in permalink generation.
 *
 * @internal This class is an internal implementation detail of our Markdown processing and is not indented for use outside of the framework.
 *
 * @see \League\CommonMark\Extension\CommonMark\Renderer\Block\HeadingRenderer
 */
class HeadingRenderer implements NodeRendererInterface
{
    /** @var ?class-string<\Hyde\Pages\Concerns\HydePage> */
    protected ?string $pageClass = null;

    /** @var array<string> */
    protected array $headingRegistry = [];

    /** @param ?class-string<\Hyde\Pages\Concerns\HydePage> $pageClass */
    public function __construct(string $pageClass = null, array &$headingRegistry = [])
    {
        $this->pageClass = $pageClass;
        $this->headingRegistry = &$headingRegistry;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! ($node instanceof Heading)) {
            throw new \InvalidArgumentException('Incompatible node type: '.get_class($node));
        }

        $content = $childRenderer->renderNodes($node->children());

        $rendered = view('hyde::components.markdown-heading', [
            'level' => $node->getLevel(),
            'slot' => $content,
            'id' => $this->makeHeadingId($content),
            'addPermalink' => $this->canAddPermalink($content, $node->getLevel()),
            'extraAttributes' => $node->data->get('attributes'),
        ])->render();

        return $this->postProcess($rendered);
    }

    /** @internal */
    public function canAddPermalink(string $content, int $level): bool
    {
        return config('markdown.permalinks.enabled', true)
            && $level >= config('markdown.permalinks.min_level', 2)
            && $level <= config('markdown.permalinks.max_level', 6)
            && ! str_contains($content, 'class="heading-permalink"')
            && in_array($this->pageClass, config('markdown.permalinks.pages', [DocumentationPage::class]));
    }

    /** @internal */
    public function postProcess(string $html): string
    {
        $html = str_replace('class=""', '', $html);
        $html = preg_replace('/<h([1-6]) >/', '<h$1>', $html);

        return implode('', array_map('trim', explode("\n", $html)));
    }

    protected function makeHeadingId(string $contents): string
    {
        $identifier = $this->ensureIdentifierIsUnique(static::makeIdentifier($contents));

        $this->headingRegistry[] = $identifier;

        return $identifier;
    }

    protected function ensureIdentifierIsUnique(string $slug): string
    {
        $identifier = $slug;
        $suffix = 2;

        while (in_array($identifier, $this->headingRegistry)) {
            $identifier = $slug.'-'.$suffix++;
        }

        return $identifier;
    }

    /** @internal */
    public static function makeIdentifier(string $title): string
    {
        return e(Str::slug(Str::transliterate(html_entity_decode($title)), dictionary: ['@' => 'at', '&' => 'and', '<' => '', '>' => '']));
    }
}
