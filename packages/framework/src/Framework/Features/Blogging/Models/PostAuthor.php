<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Blogging\Models;

use Hyde\Hyde;
use Stringable;
use Hyde\Facades\Author;
use Hyde\Pages\MarkdownPost;
use Illuminate\Support\Collection;
use Hyde\Support\Concerns\Serializable;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Support\Contracts\SerializableContract;

use function is_string;
use function array_merge;
use function array_filter;

/**
 * Object representation of a blog post author for the site.
 */
class PostAuthor implements Stringable, SerializableContract
{
    use Serializable;

    /**
     * The username of the author.
     *
     * This is the key used to find authors in the config.
     */
    public string $username;

    /**
     * The display name of the author.
     */
    public readonly string $name;

    /**
     * The author's website URL.
     *
     * Could for example, be a Twitter page, website, or a hyperlink to more posts by the author.
     * Should be a fully qualified link, meaning it starts with http:// or https://.
     */
    public readonly ?string $website;

    /**
     * The author's biography.
     */
    public readonly ?string $bio;

    /**
     * The author's avatar image.
     *
     * If you in your Blade view use `Hyde::asset($author->avatar)`, then this value supports using both image names for files in `_media`, or full URIs starting with the protocol.
     */
    public readonly ?string $avatar;

    /**
     * The author's social media links/handles.
     *
     * @var ?array<string, string>
     *
     * @example ['twitter' => 'mr_hyde'] ($service => $handle)
     */
    public readonly ?array $socials;

    /**
     * Construct a new Post Author object.
     *
     * If your input is in the form of an array, you may rather want to use the `getOrCreate` method.
     *
     * @param  string  $username
     * @param  string|null  $name
     * @param  string|null  $website
     * @param  string|null  $bio
     * @param  string|null  $avatar
     * @param  array<string, string>  $socials
     */
    public function __construct(string $username, ?string $name = null, ?string $website = null, ?string $bio = null, ?string $avatar = null, ?array $socials = null)
    {
        $this->username = $username;
        $this->name = $name ?? $username;
        $this->website = $website;
        $this->bio = $bio;
        $this->avatar = $avatar;
        $this->socials = $socials;
    }

    /**
     * Dynamically get or create an author based on a username string or front matter array.
     *
     * @param  string|array{username?: string, name?: string, website?: string, bio?: string, avatar?: string, socials?: array<string, string>}  $data
     */
    public static function getOrCreate(string|array $data): static
    {
        if (is_string($data)) {
            return static::get($data);
        }

        return new static(...array_merge([
            'username' => static::findUsernameFromData($data),
        ], $data));
    }

    /** Get an Author from the config, or create it with the username. */
    public static function get(string $username): static
    {
        return static::all()->get($username) ?? new static($username);
    }

    /** @return \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Blogging\Models\PostAuthor> */
    public static function all(): Collection
    {
        return Hyde::authors();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return array_filter($this->automaticallySerialize());
    }

    /**
     * Get all posts by this author.
     *
     * @return \Hyde\Foundation\Kernel\PageCollection<\Hyde\Pages\MarkdownPost>
     */
    public function getPosts(): PageCollection
    {
        return MarkdownPost::getLatestPosts()->filter(function (MarkdownPost $post) {
            return $post->author?->username === $this->username;
        });
    }

    /** @param array{username?: string, name?: string, website?: string} $data */
    protected static function findUsernameFromData(array $data): string
    {
        return $data['username'] ?? $data['name'] ?? 'Guest';
    }
}
