<?php

declare(strict_types=1);

namespace Hyde\Foundation\Internal;

use Hyde\Enums\Feature;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Hyde\Foundation\Application;
use Illuminate\Config\Repository;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Framework\Exceptions\InvalidConfigurationException;

use function tap;
use function array_merge;

/**
 * @internal Bootstrap service that loads the YAML configuration file.
 *
 * @see docs/digging-deeper/customization.md#yaml-configuration
 *
 * It also supports loading multiple configuration namespaces, where a configuration namespace is defined
 * as a firs level entry in the service container configuration repository array, and corresponds
 * one-to-one with a file in the config directory, and a root-level key in the YAML file.
 *
 * The namespace feature by design, requires a top-level configuration entry to be present as 'hyde' in the YAML file.
 * Existing config files will be parsed as normal, but can be migrated by indenting all entries by one level,
 * and adding a top-level 'hyde' key. Then additional namespaces can be added underneath as needed.
 */
class LoadYamlConfiguration
{
    protected YamlConfigurationRepository $yaml;

    /** @var array<string, array<string, null|scalar|array>> */
    protected array $config;

    public function bootstrap(Application $app): void
    {
        $this->yaml = $app->make(YamlConfigurationRepository::class);

        if ($this->yaml->hasYamlConfigFile()) {
            /** @var Repository $config */
            $config = $app->make('config');

            tap($config, function (Repository $config): void {
                $this->config = $config->all();
                $this->mergeParsedConfiguration();
            })->set($this->config);
        }
    }

    protected function mergeParsedConfiguration(): void
    {
        foreach ($this->yaml->getData() as $namespace => $data) {
            if ($namespace === 'hyde' && isset($data['authors'])) {
                // Todo: We may not actually need this, since the parser in the kernel can handle this. See https://github.com/hydephp/develop/pull/1824/commits/6a076b831b3cf07341605c314f0c29bfa4c0a8da
                $data['authors'] = $this->parseAuthors($data['authors']);
            }

            if ($namespace === 'hyde' && isset($data['features'])) {
                $data['features'] = $this->parseFeatures($data['features']);
            }

            $this->mergeConfiguration($namespace, Arr::undot($data ?: []));
        }
    }

    protected function mergeConfiguration(string $namespace, array $yaml): void
    {
        $this->config[$namespace] = array_merge($this->config[$namespace] ?? [], $yaml);
    }

    /**
     * @param  array<string, array{username?: string, name?: string, website?: string, bio?: string, avatar?: string, socials?: array<string, string>}>  $authors
     * @return array<string, \Hyde\Framework\Features\Blogging\Models\PostAuthor>
     */
    protected function parseAuthors(array $authors): array
    {
        return Arr::mapWithKeys($authors, function (array $author, string $username): array {
            $message = 'Invalid author configuration detected in the YAML config file. Please double check the syntax.';

            return InvalidConfigurationException::try(fn () => [$username => PostAuthor::create($author)], $message);
        });
    }

    /**
     * @param  array<string>  $features
     * @return array<\Hyde\Enums\Feature>
     */
    protected function parseFeatures(array $features): array
    {
        return array_map(function (string $feature): Feature {
            $name = Str::studly($feature);
            $case = Feature::fromName($name);

            if (! $case) {
                throw new InvalidConfigurationException("Invalid feature '$feature' specified in the YAML config file. (Feature::$name does not exist)");
            }

            return $case;
        }, $features);
    }
}
