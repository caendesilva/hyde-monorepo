<?php

declare(strict_types=1);

namespace Hyde\Support\Filesystem;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Facades\Filesystem;
use Illuminate\Support\Collection;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Illuminate\Support\Str;

use function Hyde\unslash;
use function Hyde\path_join;
use function Hyde\trim_slashes;
use function extension_loaded;
use function array_merge;

/**
 * File abstraction for a project media file.
 */
class MediaFile extends ProjectFile
{
    /** @var array<string> The default extensions for media types */
    final public const EXTENSIONS = ['png', 'svg', 'jpg', 'jpeg', 'gif', 'ico', 'css', 'js'];

    protected readonly int $length;
    protected readonly string $mimeType;
    protected readonly string $hash;

    public function __construct(string $path)
    {
        parent::__construct($this->getNormalizedPath($path));
    }

    /**
     * Get an array of media asset filenames relative to the `_media/` directory.
     *
     * @return array<int, string> {@example `['app.css', 'images/logo.svg']`}
     */
    public static function files(): array
    {
        return static::all()->keys()->all();
    }

    /**
     * Get a collection of all media files, parsed into `MediaFile` instances, keyed by the filenames relative to the `_media/` directory.
     *
     * @return \Illuminate\Support\Collection<string, \Hyde\Support\Filesystem\MediaFile>
     */
    public static function all(): Collection
    {
        return Hyde::assets();
    }

    /**
     * Get the absolute path to the media source directory, or a file within it.
     */
    public static function sourcePath(string $path = ''): string
    {
        if (empty($path)) {
            return Hyde::path(Hyde::getMediaDirectory());
        }

        return Hyde::path(path_join(Hyde::getMediaDirectory(), unslash($path)));
    }

    /**
     * Get the absolute path to the compiled site's media directory, or a file within it.
     */
    public static function outputPath(string $path = ''): string
    {
        if (empty($path)) {
            return Hyde::sitePath(Hyde::getMediaOutputDirectory());
        }

        return Hyde::sitePath(path_join(Hyde::getMediaOutputDirectory(), unslash($path)));
    }

    /**
     * Get the path to the media file relative to the media directory.
     */
    public function getIdentifier(): string
    {
        return Str::after($this->getPath(), Hyde::getMediaDirectory().'/');
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'length' => $this->getContentLength(),
            'mimeType' => $this->getMimeType(),
            'hash' => $this->getHash(),
        ]);
    }

    public function getContentLength(): int
    {
        $this->ensureInstanceIsBooted('length');

        return $this->length;
    }

    public function getMimeType(): string
    {
        $this->ensureInstanceIsBooted('mimeType');

        return $this->mimeType;
    }

    public function getHash(): string
    {
        $this->ensureInstanceIsBooted('hash');

        return $this->hash;
    }

    /** @internal */
    public static function getCacheBustKey(string $file): string
    {
        return Config::getBool('hyde.enable_cache_busting', true) && Filesystem::exists(static::sourcePath("$file"))
            ? '?v='.static::make($file)->getHash()
            : '';
    }

    protected function getNormalizedPath(string $path): string
    {
        // Ensure we are working with a relative project path
        $path = Hyde::pathToRelative($path);

        // Normalize paths using output directory to have source directory prefix
        if (str_starts_with($path, Hyde::getMediaOutputDirectory()) && str_starts_with(Hyde::getMediaDirectory(), '_')) {
            $path = '_'.$path;
        }

        // Normalize the path to include the media directory
        $path = static::sourcePath(trim_slashes(Str::after($path, Hyde::getMediaDirectory())));

        return $path;
    }

    protected function findContentLength(): int
    {
        return Filesystem::size($this->getPath());
    }

    /** @todo Move to Filesystem::findMimeType($path) */
    protected function findMimeType(): string
    {
        $extension = $this->getExtension();

        // See if we can find a mime type for the extension instead of
        // having to rely on a PHP extension and filesystem lookups.
        $lookup = [
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'html' => 'text/html',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'json' => 'application/json',
            'js' => 'application/javascript',
            'xml' => 'application/xml',
        ];

        if (isset($lookup[$extension])) {
            return $lookup[$extension];
        }

        if (extension_loaded('fileinfo') && Filesystem::exists($this->getPath())) {
            return Filesystem::mimeType($this->getPath());
        }

        return 'text/plain';
    }

    protected function findHash(): string
    {
        return Filesystem::hash($this->getPath(), 'crc32');
    }

    protected function ensureInstanceIsBooted(string $property): void
    {
        if (! isset($this->$property)) {
            $this->boot();
        }
    }

    protected function boot(): void
    {
        if (Filesystem::missing($this->getPath())) {
            throw new FileNotFoundException($this->getPath());
        }

        $this->length = $this->findContentLength();
        $this->mimeType = $this->findMimeType();
        $this->hash = $this->findHash();
    }
}
