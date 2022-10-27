<?php

declare(strict_types=1);

namespace Hyde\Pages\DataObjects;

use Hyde\Framework\Concerns\HasFrontMatter;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Markdown\Models\Markdown;
use Hyde\Support\DateString;
use Illuminate\Contracts\Support\Arrayable;
use function strlen;
use function substr;

class BlogPostData implements Arrayable
{
    use HasFrontMatter;

    private FrontMatter $matter;
    private Markdown $markdown;

    protected readonly ?string $description;
    protected readonly ?string $category;
    protected readonly ?DateString $date;
    protected readonly ?PostAuthor $author;
    protected readonly ?FeaturedImage $image;

    public function __construct(FrontMatter $matter, Markdown $markdown)
    {
        $this->matter = $matter;
        $this->markdown = $markdown;

        $this->description = $this->makeDescription();
        $this->category = $this->makeCategory();
        $this->date = $this->makeDate();
        $this->author = $this->makeAuthor();
        $this->image = $this->makeImage();
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'category' => $this->category,
            'date' => $this->date,
            'author' => $this->author,
            'image' => $this->image,
        ];
    }

    protected function makeDescription(): string
    {
        return $this->matter('description') ?? $this->getTruncatedMarkdown($this->markdown->body());
    }

    protected function makeCategory(): ?string
    {
        return $this->matter('category');
    }

    protected function makeDate(): ?DateString
    {
        if ($this->matter('date')) {
            return new DateString($this->matter('date'));
        }

        return null;
    }

    protected function makeAuthor(): ?PostAuthor
    {
        if ($this->matter('author')) {
            return PostAuthor::make($this->matter('author'));
        }

        return null;
    }

    protected function makeImage(): ?FeaturedImage
    {
        if ($this->matter('image')) {
            return FeaturedImage::make($this->matter('image'));
        }

        return null;
    }

    private function getTruncatedMarkdown(string $markdown): string
    {
        if (strlen($markdown) >= 128) {
            return substr($markdown, 0, 125).'...';
        }

        return $markdown;
    }
}
