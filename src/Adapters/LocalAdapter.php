<?php

/**
 * LocalAdapter.php
 *
 * This file is part of InitPHP Upload.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    https://github.com/InitPHP/Upload/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload\Adapters;

use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;
use Throwable;

use function array_merge;
use function copy;
use function is_dir;
use function ltrim;
use function mkdir;
use function move_uploaded_file;
use function rtrim;
use function str_replace;
use function strrpos;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Stores uploaded files on the local filesystem.
 *
 * Genuine HTTP uploads are relocated with `move_uploaded_file()`; files loaded
 * from an arbitrary path with {@see File::setPath()} are copied instead, so the
 * caller's original file is preserved. Missing destination directories are
 * created automatically.
 */
class LocalAdapter extends BaseUploadAdapter
{
    /**
     * Local credentials.
     *
     * - `dir`: absolute base directory files are written into.
     * - `url`: public base URL the directory is served from.
     *
     * @var array{dir: string, url: string}
     */
    protected const CREDENTIALS = [
        'dir' => '',
        'url' => '',
    ];

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $options
     */
    public function __construct(array $credentials, array $options = [])
    {
        parent::__construct(array_merge(self::CREDENTIALS, $credentials), $options);
    }

    /**
     * {@inheritDoc}
     */
    public function with(): self
    {
        return clone $this;
    }

    /**
     * {@inheritDoc}
     */
    public function to($target = null)
    {
        try {
            $this->checkFile();

            $name = $this->targetName($target);
            $path = rtrim($this->stringCredential('dir'), '\\/') . DIRECTORY_SEPARATOR . $name;

            $this->ensureDirectory($this->dirname($path));

            if (!$this->transfer($this->file, $path)) {
                return false;
            }

            $url = rtrim($this->stringCredential('url'), '/') . '/' . ltrim(str_replace('\\', '/', $name), '/');
            $this->file->setURL($url);

            return $this->file;
        } catch (UploadException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UploadException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Moves an HTTP upload or copies a path-loaded file to its destination.
     *
     * @param File   $file        The file to store.
     * @param string $destination Absolute destination path.
     * @return bool Whether the write succeeded.
     */
    protected function transfer(File $file, string $destination): bool
    {
        $source = $file->getRealPath();

        return $file->isUploaded()
            ? move_uploaded_file($source, $destination)
            : copy($source, $destination);
    }

    /**
     * Ensures the given directory exists, creating it recursively if needed.
     *
     * @param string $directory
     * @return void
     * @throws UploadException When the directory cannot be created.
     */
    protected function ensureDirectory(string $directory): void
    {
        if ($directory === '' || is_dir($directory)) {
            return;
        }
        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new UploadException(\sprintf('The target directory "%s" could not be created.', $directory));
        }
    }

    /**
     * Returns the directory portion of a path, normalizing separators so the
     * result is correct regardless of how `$target` was written.
     *
     * @param string $path
     * @return string
     */
    private function dirname(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $position = strrpos($normalized, '/');

        if ($position === false) {
            return '';
        }

        return str_replace('/', DIRECTORY_SEPARATOR, substr($normalized, 0, $position));
    }
}
