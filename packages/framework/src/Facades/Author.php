<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Illuminate\Support\Collection;

use function compact;

/**
 * Allows you to easily add pre-defined authors for your blog posts.
 *
 * @see \Hyde\Framework\Features\Blogging\Models\PostAuthor
 */
class Author
{
    /**
     * Configuration helper method to define a new blog post author, with better IDE support.
     *
     * The returned array will then be used by the framework to create a new PostAuthor instance.
     *
     * @see https://hydephp.com/docs/1.x/customization#authors
     *
     * @param  string|null  $name  The optional display name of the author, leave blank to use the username.
     * @param  string|null  $website  The author's optional website URL. Website, Twitter, etc.
     * @param  string|null  $bio  The author's optional biography text. Markdown supported.
     * @param  string|null  $avatar  The author's optional avatar image. Supports both image names and full URIs.
     * @param  array<string, string>|null  $socials  The author's optional social media links/handles.
     */
    public static function create(?string $name = null, ?string $website = null, ?string $bio = null, ?string $avatar = null, ?array $socials = null): array
    {
        return compact('name', 'website', 'bio', 'avatar', 'socials');
    }

    /**
     * Get a Post Author instance by username, or null if not found.
     */
    public static function get(string $username): ?PostAuthor
    {
        return PostAuthor::get($username);
    }

    /**
     * Get all the defined Post Author instances from the config.
     *
     * @return \Illuminate\Support\Collection<\Hyde\Framework\Features\Blogging\Models\PostAuthor>
     */
    public static function all(): Collection
    {
        return PostAuthor::all();
    }
}
