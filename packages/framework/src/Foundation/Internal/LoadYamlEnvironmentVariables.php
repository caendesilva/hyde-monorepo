<?php

declare(strict_types=1);

namespace Hyde\Foundation\Internal;

use Illuminate\Support\Env;
use Hyde\Foundation\Application;

/**
 * @internal Inject environment variables parsed from the YAML configuration file.
 */
class LoadYamlEnvironmentVariables
{
    protected YamlConfigurationRepository $yaml;

    /**
     * Performs a core task that needs to be performed on
     * early stages of the framework.
     */
    public function bootstrap(Application $app): void
    {
        $this->yaml = $app->make(YamlConfigurationRepository::class);

        if ($this->yaml->hasYamlConfigFile()) {
            $this->injectEnvironmentVariables();
        }
    }

    protected function injectEnvironmentVariables(): void
    {
        if ($this->canInjectSiteNameEnvironmentVariable()) {
            $this->injectSiteNameEnvironmentVariable();
        }
    }

    protected function canInjectSiteNameEnvironmentVariable(): bool
    {
        return $this->yamlHasSiteNameSet()
            && ! Env::get('SITE_NAME');
    }

    protected function injectSiteNameEnvironmentVariable(): void
    {
        // Todo
    }

    protected function yamlHasSiteNameSet(): bool
    {
        return isset($this->yaml->getData()['hyde']['name']) || isset($this->yaml->getData()['name']);
    }
}
