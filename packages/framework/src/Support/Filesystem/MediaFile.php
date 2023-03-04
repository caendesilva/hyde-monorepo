<?php

declare(strict_types=1);

namespace Hyde\Support\Filesystem;

use Hyde\Hyde;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Services\DiscoveryService;
use Illuminate\Support\Str;
use function extension_loaded;
use function array_merge;
use function file_exists;
use function filesize;
use function pathinfo;
use function collect;
use function is_file;

/**
 * File abstraction for a project media file.
 */
class MediaFile extends ProjectFile
{
    /** @return array<string, \Hyde\Support\Filesystem\MediaFile> */
    public static function all(): array
    {
        return static::discoverMediaAssetFiles();
    }

    public function getIdentifier(): string
    {
        return Str::after($this->getPath(), Hyde::getMediaDirectory().'/');
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'length' => $this->getContentLength(),
            'mimeType' => $this->getMimeType(),
        ]);
    }

    public function getContentLength(): int
    {
        if (! is_file($this->getAbsolutePath())) {
            throw new FileNotFoundException(message: "Could not get the content length of file [$this->path]");
        }

        return filesize($this->getAbsolutePath());
    }

    public function getMimeType(): string
    {
        $extension = pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);

        // See if we can find a mime type for the extension instead of
        // having to rely on a PHP extension and filesystem lookups.
        $lookup = [
            'txt'  => 'text/plain',
            'md'   => 'text/markdown',
            'html' => 'text/html',
            'css'  => 'text/css',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'json' => 'application/json',
            'js'   => 'application/javascript',
            'xml'  => 'application/xml',
        ];

        if (isset($lookup[$extension])) {
            return $lookup[$extension];
        }

        if (extension_loaded('fileinfo') && file_exists($this->getAbsolutePath())) {
            return mime_content_type($this->getAbsolutePath());
        }

        return 'text/plain';
    }

    protected static function discoverMediaAssetFiles(): array
    {
        return collect(DiscoveryService::getMediaAssetFiles())->mapWithKeys(function (string $filepath): array {
            $file = static::make($filepath);
            return [$file->getPath() => $file];
        })->all();
    }
}
