<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Hyde;
use Illuminate\Support\Str;

use function sprintf;

/**
 * HydeFront is the NPM package that bundles the default precompiled CSS and JavaScript assets for HydePHP.
 *
 * This facade makes it easy to access these assets from the HydeFront CDN, automatically getting the correct version.
 */
class HydeFront
{
    /** @var string The HydeFront SemVer tag to load. This constant is set to match the styles used for the installed framework version. */
    protected const HYDEFRONT_VERSION = 'v3.4';

    /** @var string The HydeFront CDN path pattern used to assemble CDN links. */
    protected const HYDEFRONT_CDN_URL = 'https://cdn.jsdelivr.net/npm/hydefront@%s/dist/%s';

    /**
     * Get the current version of the HydeFront package.
     *
     * @return string {@see HYDEFRONT_VERSION}
     */
    public static function version(): string
    {
        return static::HYDEFRONT_VERSION;
    }

    /** Get the CDN link for the HydeFront CSS file. This will return the default `app.css` file. */
    public static function cdnLink(): string
    {
        return sprintf(static::HYDEFRONT_CDN_URL, static::version(), 'app.css');
    }

    /** This method is used to inject the project's Tailwind CSS configuration into the Play CDN integration so it can match the styles. */
    public static function injectTailwindConfig(): string
    {
        if (! file_exists(Hyde::path('tailwind.config.js'))) {
            return '';
        }

        $config = Str::between(file_get_contents(Hyde::path('tailwind.config.js')), '{', '}');

        // Remove the plugins array, as it is not used in the frontend build.
        if (str_contains($config, 'plugins: [')) {
            $tokens = explode('plugins: [', $config, 2);
            $tokens[1] = Str::after($tokens[1], ']');
            $config = implode('', $tokens);
        }

        return preg_replace('/\s+/', ' ', "/* tailwind.config.js */ \n".rtrim($config, ",\n\r"));
    }
}
