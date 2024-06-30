<?php

declare(strict_types=1);

namespace Hyde\Foundation\Internal;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

use function array_key_first;
use function file_get_contents;
use function array_merge;
use function file_exists;

/**
 * @internal Bootstrap service that loads the YAML configuration file.
 *
 * @see docs/digging-deeper/customization.md#yaml-configuration
 *
 * It also supports loading multiple configuration namespaces, where a configuration namespace is defined
 * as a firs level entry in the service container configuration repository array, and corresponds
 * one-to-one with a file in the config directory, and a root-level key in the YAML file.
 *
 * This feature, by design, requires a top-level configuration entry to be present as 'hyde' in the YAML file.
 * Existing config files will be parsed as normal, but can be migrated by indenting all entries by one level,
 * and adding a top-level 'hyde' key. Then additional namespaces can be added underneath as needed.
 */
class LoadYamlConfiguration
{
    protected array $config;
    protected array $yaml;

    /**
     * Performs a core task that needs to be performed on
     * early stages of the framework.
     */
    public function bootstrap(): void
    {
        $this->config = Config::all();

        if ($this->hasYamlConfigFile()) {
            $this->yaml = $this->getYaml();

            $this->mergeParsedConfiguration();

            Config::set($this->config);
        }
    }

    protected function hasYamlConfigFile(): bool
    {
        return file_exists(Hyde::path('hyde.yml'))
            || file_exists(Hyde::path('hyde.yaml'));
    }

    /** @return array<string, scalar> */
    protected function getYaml(): array
    {
        return Arr::undot((array) Yaml::parse(file_get_contents($this->getFile())));
    }

    protected function getFile(): string
    {
        return file_exists(Hyde::path('hyde.yml'))
            ? Hyde::path('hyde.yml')
            : Hyde::path('hyde.yaml');
    }

    protected function mergeParsedConfiguration(): void
    {
        $yaml = $this->yaml;

        // If the Yaml file contains namespaces, we merge those using more granular logic
        // that only applies the namespace data to each configuration namespace.
        if ($this->configurationContainsNamespaces($yaml)) {
            /** @var array<string, array<string, scalar>> $yaml */
            foreach ($yaml as $namespace => $data) {
                $this->mergeConfiguration($namespace, Arr::undot((array) $data));
            }

            return;
        }

        // Otherwise, we can merge using the default strategy, which is simply applying all the data to the hyde namespace.
        $this->mergeConfiguration('hyde', $yaml);
    }

    protected function mergeConfiguration(string $namespace, array $yamlData): void
    {
        $this->config[$namespace] = array_merge(
            $this->config[$namespace] ?? [],
            $yamlData
        );
    }

    protected function configurationContainsNamespaces(array $yaml): bool
    {
        return array_key_first($yaml) === 'hyde';
    }
}
