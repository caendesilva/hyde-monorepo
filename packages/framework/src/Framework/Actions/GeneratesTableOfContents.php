<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Facades\Config;
use Hyde\Markdown\Models\Markdown;
use Hyde\Markdown\Processing\HeadingRenderer;

/**
 * Generates a nested table of contents from Markdown headings.
 */
class GeneratesTableOfContents
{
    protected string $markdown;

    protected int $minHeadingLevel = 2;
    protected int $maxHeadingLevel = 4;

    public function __construct(Markdown|string $markdown)
    {
        $this->markdown = (string) $markdown;

        $this->minHeadingLevel = Config::getInt('docs.sidebar.table_of_contents.min_heading_level', 2);
        $this->maxHeadingLevel = Config::getInt('docs.sidebar.table_of_contents.max_heading_level', 4);
    }

    /** @return array<int, array{title: string, identifier: string, children: array}> */
    public function execute(): array
    {
        return $this->buildTableOfContents($this->parseHeadings());
    }

    /** @return array<int, array{level: int, title: string, identifier: string}> */
    protected function parseHeadings(): array
    {
        $matches = $this->matchHeadingPatterns();
        $headings = [];

        foreach ($matches[0] as $index => $heading) {
            $headingData = $this->parseHeadingData($heading, $matches, $index);

            if ($headingData !== null) {
                $headings[] = $this->createHeadingEntry($headingData);
            }
        }

        return $headings;
    }

    /** @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>} */
    protected function matchHeadingPatterns(): array
    {
        // Match both ATX-style (###) and Setext-style (===, ---) headers
        $pattern = '/^(?:#{1,6}\s+(.+)|(.+)\n([=\-])\3+)$/m';

        preg_match_all($pattern, $this->markdown, $matches);

        return $matches;
    }

    /**
     * @param  array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>, 3: array<int, string>}  $matches
     * @return array{level: int, title: string}|null
     */
    protected function parseHeadingData(string $heading, array $matches, int $index): ?array
    {
        if (str_starts_with($heading, '#')) {
            return $this->parseAtxHeading($heading, $matches[1][$index]);
        }

        return $this->parseSetextHeading($matches[2][$index], $matches[3][$index]);
    }

    /** @return array{level: int, title: string} */
    protected function parseAtxHeading(string $heading, string $title): array
    {
        return [
            'level' => substr_count($heading, '#'),
            'title' => $title,
        ];
    }

    /** @return array{level: int, title: string}|null */
    protected function parseSetextHeading(string $title, string $marker): ?array
    {
        $level = $marker === '=' ? 1 : 2;

        if ($level < $this->minHeadingLevel) {
            return null;
        }

        return [
            'level' => $level,
            'title' => trim($title),
        ];
    }

    /**
     * @param  array{level: int, title: string}  $headingData
     * @return array{level: int, title: string, identifier: string}
     */
    protected function createHeadingEntry(array $headingData): array
    {
        return [
            'level' => $headingData['level'],
            'title' => $headingData['title'],
            'identifier' => HeadingRenderer::makeIdentifier($headingData['title']),
        ];
    }

    /**
     * @param  array<int, array{level: int, title: string, identifier: string}>  $headings
     * @return array<int, array{title: string, identifier: string, children: array}>
     */
    protected function buildTableOfContents(array $headings): array
    {
        $items = [];
        $stack = [&$items];
        $previousLevel = $this->minHeadingLevel;

        foreach ($headings as $heading) {
            if ($this->isHeadingWithinBounds($heading)) {
                $item = $this->createTableItem($heading);
                $this->updateStackForHeadingLevel($stack, $heading['level'], $previousLevel);

                $stack[count($stack) - 1][] = $item;
                $previousLevel = $heading['level'];
            }
        }

        return array_values($items);
    }

    /** @param array{level: int, title: string, identifier: string}  $heading */
    protected function isHeadingWithinBounds(array $heading): bool
    {
        return $heading['level'] >= $this->minHeadingLevel &&
               $heading['level'] <= $this->maxHeadingLevel;
    }

    /**
     * @param  array{level: int, title: string, identifier: string}  $heading
     * @return array{title: string, identifier: string, children: array}
     */
    protected function createTableItem(array $heading): array
    {
        return [
            'title' => $heading['title'],
            'identifier' => $heading['identifier'],
            'children' => [],
        ];
    }

    /** @param  array<int, array<int, array{title: string, identifier: string, children: array}>> $stack */
    protected function updateStackForHeadingLevel(array &$stack, int $currentLevel, int $previousLevel): void
    {
        if ($currentLevel > $previousLevel) {
            $this->nestNewLevel($stack);
        }

        if ($currentLevel < $previousLevel) {
            $this->unwindStack($stack, $currentLevel);
        }
    }

    /** @param array<int, array<int, array{title: string, identifier: string, children: array}>> $stack */
    protected function nestNewLevel(array &$stack): void
    {
        $lastStackIndex = count($stack) - 1;
        $lastItemIndex = count($stack[$lastStackIndex]) - 1;

        $stack[] = &$stack[$lastStackIndex][$lastItemIndex]['children'];
    }

    /** @param array<int, array<int, array{title: string, identifier: string, children: array}>> $stack */
    protected function unwindStack(array &$stack, int $currentLevel): void
    {
        array_splice($stack, $currentLevel - $this->minHeadingLevel + 1);
    }
}
