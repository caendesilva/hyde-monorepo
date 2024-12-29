<?php

declare(strict_types=1);

namespace {
    use Hyde\Foundation\HydeKernel;
    use Hyde\Support\Filesystem\MediaFile;

    if (! function_exists('hyde')) {
        /**
         * Get the available HydeKernel instance.
         */
        function hyde(): HydeKernel
        {
            return HydeKernel::getInstance();
        }
    }

    if (defined('HYDE_COMPATIBILITY_MODE') && HYDE_COMPATIBILITY_MODE === true) {
        // Don't declare these functions when running in compatibility mode.
    } else {
        if (! function_exists('asset')) {
            /**
             * Gets a MediaAsset instance for the given file stored in the `_site/media` folder.
             * The returned value can be cast into a string in Blade views to resole the URL.
             *
             * If a base URL is configured, the image will be returned with a qualified absolute URL.
             * Otherwise, a relative path will be returned based on the rendered page's location.
             *
             * @throws \Hyde\Framework\Exceptions\FileNotFoundException If the file does not exist in the `_media` source directory.
             */
            function asset(string $name): MediaFile
            {
                return hyde()->asset($name);
            }
        }

        if (! function_exists('route')) {
            /**
             * Get a page route instance by its key. Casting it to a string will return a relative link to the page.
             */
            function route(string $key): ?Hyde\Support\Models\Route
            {
                return hyde()->routes()->getRoute($key);
            }
        }

        if (! function_exists('url')) {
            /**
             * Get a qualified URL to the supplied path if a base URL is set.
             */
            function url(string $path = ''): string
            {
                return hyde()->url($path);
            }
        }
    }
}

namespace Hyde {
    use Hyde\Facades\Filesystem;
    use Hyde\Foundation\HydeKernel;
    use Illuminate\Contracts\Support\Arrayable;
    use Symfony\Component\Yaml\Yaml;

    use function function_exists;
    use function array_merge;
    use function str_replace;
    use function implode;
    use function trim;
    use function md5;

    if (! function_exists('\Hyde\hyde')) {
        /**
         * Get the available HydeKernel instance.
         */
        function hyde(): HydeKernel
        {
            return HydeKernel::getInstance();
        }
    }

    if (! function_exists('\Hyde\unslash')) {
        /**
         * Remove trailing slashes from the start and end of a string.
         */
        function unslash(string $string): string
        {
            return trim($string, '/\\');
        }
    }

    if (! function_exists('\Hyde\unixsum')) {
        /**
         * A EOL agnostic wrapper for calculating MD5 checksums.
         *
         * This function is not cryptographically secure.
         */
        function unixsum(string $string): string
        {
            return md5(str_replace(["\r\n", "\r"], "\n", $string));
        }
    }

    if (! function_exists('\Hyde\unixsum_file')) {
        /**
         * Shorthand for {@see unixsum()} but loads a file.
         */
        function unixsum_file(string $file): string
        {
            return \Hyde\unixsum(Filesystem::getContents($file));
        }
    }

    if (! function_exists('\Hyde\make_title')) {
        function make_title(string $value): string
        {
            return hyde()->makeTitle($value);
        }
    }

    if (! function_exists('\Hyde\normalize_newlines')) {
        function normalize_newlines(string $string): string
        {
            return hyde()->normalizeNewlines($string);
        }
    }

    if (! function_exists('\Hyde\strip_newlines')) {
        function strip_newlines(string $string): string
        {
            return hyde()->stripNewlines($string);
        }
    }

    if (! function_exists('\Hyde\trim_slashes')) {
        function trim_slashes(string $string): string
        {
            return hyde()->trimSlashes($string);
        }
    }

    if (! function_exists('\Hyde\evaluate_arrayable')) {
        function evaluate_arrayable(array|Arrayable $array): array
        {
            return $array instanceof Arrayable ? $array->toArray() : $array;
        }
    }

    if (! function_exists('\Hyde\yaml_encode')) {
        function yaml_encode(mixed $input, int $inline = 2, int $indent = 4, int $flags = 0): string
        {
            return Yaml::dump($input instanceof Arrayable ? $input->toArray() : $input, $inline, $indent, $flags);
        }
    }

    if (! function_exists('\Hyde\yaml_decode')) {
        function yaml_decode(string $input, int $flags = 0): mixed
        {
            return Yaml::parse($input, $flags);
        }
    }

    if (! function_exists('\Hyde\path_join')) {
        function path_join(string $directory, string ...$paths): string
        {
            return implode('/', array_merge([$directory], $paths));
        }
    }

    if (! function_exists('\Hyde\normalize_slashes')) {
        function normalize_slashes(string $string): string
        {
            return str_replace('\\', '/', $string);
        }
    }
}
