<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Blogging\Models;

use Hyde\Hyde;
use Stringable;
use Illuminate\Support\Str;
use Hyde\Pages\MarkdownPost;
use Illuminate\Support\Collection;
use Hyde\Support\Concerns\Serializable;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Support\Contracts\SerializableContract;

use function array_merge;
use function array_filter;

/**
 * Object representation of a blog post author for the site.
 *
 * @see \Hyde\Facades\Author For the facade to conveniently interact with and create authors.
 */
class PostAuthor implements Stringable, SerializableContract
{
    use Serializable;

    /**
     * The username of the author.
     *
     * This is the key used to find authors in the config and is taken from that array key.
     */
    public readonly string $username;

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
     * @var ?array<string, string> String-to-string map of social media services to their respective handles.
     *
     * @example ['twitter' => 'mr_hyde'] ($service => $handle)
     */
    public readonly ?array $socials;

    /**
     * Construct a new Post Author instance with the given data.
     *
     * If your input is in the form of an array, you may rather want to use the `create` method.
     *
     * @param  array<string, string>  $socials
     */
    public function __construct(string $username, ?string $name = null, ?string $website = null, ?string $bio = null, ?string $avatar = null, ?array $socials = null)
    {
        $this->username = static::normalizeUsername($username);
        $this->name = $name ?? static::generateName($username);
        $this->website = $website;
        $this->bio = $bio;
        $this->avatar = $avatar;
        $this->socials = $socials;
    }

    /**
     * Create a new Post Author instance from an array of data.
     *
     * If you do not supply a username, the name will be used as the username, or 'Guest' if no name is provided.
     *
     * @param  array{username?: string, name?: string, website?: string, bio?: string, avatar?: string, socials?: array<string, string>}  $data
     */
    public static function create(array $data): PostAuthor
    {
        return new static(...array_merge([
            'username' => static::findUsernameFromData($data),
        ], $data));
    }

    /** Get a Post Author instance by username, or null if not found. */
    public static function get(string $username): ?static
    {
        return static::all()->get(static::normalizeUsername($username));
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
        return static::normalizeUsername($data['username'] ?? $data['name'] ?? 'guest');
    }

    /** @internal */
    public static function normalizeUsername(string $username): string
    {
        return Str::slug($username, '_');
    }

    protected static function generateName(string $username): string
    {
        return Str::headline($username);
    }
}
