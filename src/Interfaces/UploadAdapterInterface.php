<?php

/**
 * UploadAdapterInterface.php
 *
 * This file is part of InitPHP Upload.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    https://github.com/InitPHP/Upload/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload\Interfaces;

use InitPHP\Upload\File;

/**
 * Contract every storage adapter implements.
 *
 * The `set*` methods mutate the current adapter and return it, while the
 * `with*` methods clone the adapter first (releasing any open connection or
 * client) so the original instance is left untouched.
 */
interface UploadAdapterInterface
{
    /**
     * Returns a clone of the adapter with any open connection or client
     * released, so the copy reconnects lazily on first use.
     *
     * @return self
     */
    public function with(): self;

    /**
     * Sets a single option on the current adapter.
     *
     * @param string $name  Option name (e.g. `allowed_max_size`).
     * @param mixed  $value Option value.
     * @return self
     */
    public function setOption(string $name, $value): self;

    /**
     * Merges the given options into the current adapter.
     *
     * @param array<string, mixed> $options
     * @return self
     */
    public function setOptions(array $options): self;

    /**
     * Like {@see setOption()} but applied to a fresh clone of the adapter.
     *
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function withOption(string $name, $value): self;

    /**
     * Like {@see setOptions()} but applied to a fresh clone of the adapter.
     *
     * @param array<string, mixed> $options
     * @return self
     */
    public function withOptions(array $options): self;

    /**
     * Merges the given credentials into the current adapter.
     *
     * @param array<string, mixed> $credentials
     * @return self
     */
    public function setCredentials(array $credentials): self;

    /**
     * Like {@see setCredentials()} but applied to a fresh clone of the adapter.
     *
     * @param array<string, mixed> $credentials
     * @return self
     */
    public function withCredentials(array $credentials): self;

    /**
     * Sets the file that the next {@see to()} call will store.
     *
     * @param File $file
     * @return self
     */
    public function setFile(File $file): self;

    /**
     * Validates and stores the current file.
     *
     * `$target` is interpreted consistently across adapters as a destination
     * path/key prefix (e.g. `avatars` stores the file under `avatars/`). On
     * success the stored {@see File} is returned with its public URL set; on a
     * non-exceptional failure (e.g. the underlying write returned false) the
     * method returns `false`.
     *
     * @param string|null $target Optional destination prefix.
     * @return File|false
     * @throws \InitPHP\Upload\Exceptions\UploadException When validation or the
     *         transfer fails.
     */
    public function to($target = null);
}
