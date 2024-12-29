<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use Hyde\Facades\Config;
use Illuminate\Support\Collection;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Framework\Exceptions\InvalidConfigurationException;

use function collect;

/**
 * Contains accessors and containers for general data stored in the kernel.
 *
 * @internal Single-use trait for the HydeKernel class.
 *
 * @see \Hyde\Foundation\HydeKernel
 */
trait HasKernelData
{
    /**
     * The collection of authors defined in the config.
     *
     * @var \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Blogging\Models\PostAuthor>
     */
    protected Collection $authors;

    /**
     * Get the collection of authors defined in the config.
     *
     * @return \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Blogging\Models\PostAuthor>
     */
    public function authors(): Collection
    {
        if (isset($this->authors)) {
            return $this->authors;
        }

        $config = collect(Config::getArray('hyde.authors', []));

        if ($config->isEmpty()) {
            // Defer setting the authors property until the next try.
            return $config;
        }

        return $this->authors = $this->parseConfigurationAuthors($config);
    }

    protected function parseConfigurationAuthors(Collection $authors): Collection
    {
        return $authors->mapWithKeys(function (array $author, string $username): array {
            if (! $username) {
                throw new InvalidConfigurationException('Author username cannot be empty. Did you forget to set the author\'s array key?', 'hyde', 'authors');
            }

            $username = PostAuthor::normalizeUsername($username);

            $author['username'] = $username;

            return [$username => PostAuthor::create($author)];
        });
    }
}
