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

    /** @internal Controls whether to validate the existence of the file, intended for unit testing. Turning this off may lead to unexpected behavior. */
    public static bool $validateExistence = true;

    protected int $length;
    protected string $mimeType;
    protected string $hash;

    public function __construct(string $path)
    {
        $path = $this->normalizePath($path);

        if (static::$validateExistence && Filesystem::missing($path)) {
            throw new FileNotFoundException($path);
        }

        parent::__construct($path);

        $this->length = $this->findContentLength();
        $this->mimeType = $this->findMimeType();
        $this->hash = $this->findHash();
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
     * Get a collection of all media files, parsed into MediaFile instances, keyed by the filenames relative to the `_media/` directory.
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
        return $this->length;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /** @internal */
    public static function getCacheBustKey(string $file): string
    {
        return Config::getBool('hyde.enable_cache_busting', true) && Filesystem::exists(static::sourcePath("$file"))
            ? '?v='.static::make($file)->getHash()
            : '';
    }

    protected function normalizePath(string $path): string
    {
        $path = Hyde::pathToRelative($path);

        // Normalize paths using output directory to have source directory prefix
        if (str_starts_with($path, Hyde::getMediaOutputDirectory()) && str_starts_with(Hyde::getMediaDirectory(), '_')) {
            $path = '_'.$path;
        }

        return static::sourcePath(trim_slashes(Str::after($path, Hyde::getMediaDirectory())));
    }

    protected function findContentLength(): int
    {
        return Filesystem::size($this->getPath());
    }

    protected function findMimeType(): string
    {
        $extension = Filesystem::extension($this->getPath());

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
}
