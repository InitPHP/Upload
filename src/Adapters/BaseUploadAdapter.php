<?php

/**
 * BaseUploadAdapter.php
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
use InitPHP\Upload\Interfaces\UploadAdapterInterface;

use function array_merge;
use function is_numeric;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Shared behaviour for every upload adapter: credential/option management and
 * pre-transfer validation. Concrete adapters implement {@see with()} and
 * {@see to()} for their specific storage backend.
 */
abstract class BaseUploadAdapter implements UploadAdapterInterface
{
    /**
     * Default validation options. An empty/zero value means "no restriction".
     *
     * @var array{allowed_extensions: string[], allowed_mime_types: string[], allowed_max_size: int}
     */
    protected const CONST_OPTIONS = [
        'allowed_extensions' => [],
        'allowed_mime_types' => [],
        'allowed_max_size'   => 0,
    ];

    /** @var array<string, mixed> */
    protected array $credentials = [];

    /** @var array<string, mixed> */
    protected array $options = [];

    /** The file queued for upload by {@see setFile()}. */
    protected File $file;

    /**
     * @param array<string, mixed> $credentials Backend connection details.
     * @param array<string, mixed> $options     Validation options; merged over
     *                                           {@see CONST_OPTIONS}.
     */
    public function __construct(array $credentials, array $options)
    {
        $this->setCredentials($credentials);
        $this->setOptions(array_merge(self::CONST_OPTIONS, $options));
    }

    /**
     * {@inheritDoc}
     */
    abstract public function with(): self;

    /**
     * {@inheritDoc}
     */
    public function setCredentials(array $credentials): self
    {
        if (!empty($credentials)) {
            $this->credentials = array_merge($this->credentials, $credentials);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withCredentials(array $credentials): self
    {
        return $this->with()->setCredentials($credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withOption(string $name, $value): self
    {
        return $this->with()->setOption($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function withOptions(array $options): self
    {
        return $this->with()->setOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function to($target = null);

    /**
     * Validates the queued file against the configured options.
     *
     * @return void
     * @throws UploadException When no file is queued, the upload errored, or a
     *         configured restriction (extension, MIME type, size) is violated.
     */
    protected function checkFile(): void
    {
        if (!isset($this->file)) {
            throw new UploadException('The file to be uploaded is undefined.');
        }
        if (!$this->file->isValid()) {
            throw new UploadException(\sprintf('The file upload failed with error code %d.', $this->file->getError()));
        }

        $allowedExtensions = $this->arrayOption('allowed_extensions');
        if ($allowedExtensions !== []) {
            $allowed = [];
            foreach ($allowedExtensions as $extension) {
                if (\is_string($extension)) {
                    $allowed[] = strtolower($extension);
                }
            }
            if (!\in_array($this->file->getExtension(), $allowed, true)) {
                throw new UploadException('This file extension is not allowed.');
            }
        }

        $allowedMimeTypes = $this->arrayOption('allowed_mime_types');
        if ($allowedMimeTypes !== [] && !\in_array($this->file->getRealMimeType(), $allowedMimeTypes, true)) {
            throw new UploadException('This file type is not allowed.');
        }

        $maxSize = $this->intOption('allowed_max_size');
        if ($maxSize > 0 && $maxSize < $this->file->getSize()) {
            throw new UploadException('Exceeds the maximum uploadable file size.');
        }
    }

    /**
     * Builds the destination name for the queued file, prefixing it with the
     * normalized `$target` path when one is given.
     *
     * The returned name always uses forward slashes; the original (or renamed)
     * file name is appended last.
     *
     * @param string|null $target Optional destination prefix.
     * @return string
     */
    protected function targetName($target): string
    {
        $name = $this->file->getReName() ?? $this->file->getName();

        if (!\is_string($target) || $target === '') {
            return $name;
        }
        $prefix = trim(str_replace('\\', '/', $target), '/');

        return $prefix === '' ? $name : $prefix . '/' . $name;
    }

    /**
     * Reads a credential as a string, returning the default when it is unset or
     * not a scalar value.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function stringCredential(string $key, string $default = ''): string
    {
        $value = $this->credentials[$key] ?? $default;

        return \is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Reads a credential as an int, returning the default when it is unset or
     * not numeric.
     *
     * @param string $key
     * @param int    $default
     * @return int
     */
    protected function intCredential(string $key, int $default = 0): int
    {
        $value = $this->credentials[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Reads a credential as a bool.
     *
     * @param string $key
     * @param bool   $default
     * @return bool
     */
    protected function boolCredential(string $key, bool $default = false): bool
    {
        $value = $this->credentials[$key] ?? $default;

        return (bool) $value;
    }

    /**
     * Reads an option as an array, returning an empty array when it is unset or
     * not an array.
     *
     * @param string $key
     * @return array<array-key, mixed>
     */
    protected function arrayOption(string $key): array
    {
        $value = $this->options[$key] ?? [];

        return \is_array($value) ? $value : [];
    }

    /**
     * Reads an option as an int, returning the default when it is unset or not
     * numeric.
     *
     * @param string $key
     * @param int    $default
     * @return int
     */
    protected function intOption(string $key, int $default = 0): int
    {
        $value = $this->options[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }
}
