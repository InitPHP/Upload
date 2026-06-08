<?php

/**
 * File.php
 *
 * This file is part of InitPHP Upload.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    https://github.com/InitPHP/Upload/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload;

use InitPHP\Upload\Exceptions\UploadInvalidArgumentException;

use function array_keys;
use function basename;
use function file_exists;
use function filesize;
use function is_numeric;
use function is_uploaded_file;
use function mime_content_type;
use function pathinfo;
use function realpath;
use function strtolower;
use function trim;

use const PATHINFO_EXTENSION;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * An immutable-ish value object describing a single file that is about to be
 * uploaded.
 *
 * A `File` can wrap either a temporary file created by an HTTP upload (built
 * through {@see File::setPost()}) or any readable path on the local
 * filesystem ({@see File::setPath()}). The original client file name — not the
 * temporary path — is the source of truth for the name and the extension.
 */
final class File
{
    /**
     * The source path the file is read from. For HTTP uploads this is the
     * `tmp_name`; for {@see setPath()} it is the path given by the caller.
     */
    private string $path;

    /** The client/original file name, e.g. `holiday.JPG`. */
    private string $name;

    /** The file size in bytes. */
    private int $size;

    /** The declared MIME type (client supplied for uploads, untrusted). */
    private string $type;

    /** The lower-cased extension derived from {@see $name}, without the dot. */
    private string $extension;

    /** One of PHP's `UPLOAD_ERR_*` constants. */
    private int $error;

    /** The public URL assigned once the file has been stored, if any. */
    private ?string $url = null;

    /** The target name the file should be stored as, if {@see rename()} was used. */
    private ?string $rename = null;

    /** Cached result of the `finfo` based MIME detection. */
    private ?string $realType = null;

    /**
     * @param string      $path  Path the file is read from (an upload `tmp_name`
     *                           or any readable local path).
     * @param string|null $name  Original client file name; defaults to the
     *                           base name of `$path`.
     * @param int|null    $size  Size in bytes; detected from `$path` when null.
     * @param string|null $type  Declared MIME type; detected from `$path` when
     *                           null.
     * @param int         $error A PHP `UPLOAD_ERR_*` code; defaults to
     *                           `UPLOAD_ERR_OK`.
     */
    public function __construct(
        string $path,
        ?string $name = null,
        ?int $size = null,
        ?string $type = null,
        int $error = UPLOAD_ERR_OK
    ) {
        $this->path = $path;
        $this->name = $name ?? basename($path);
        $this->extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
        $this->error = $error;
        $this->size = $size ?? $this->detectSize($path);
        $this->type = $type ?? ($this->detectMimeType($path) ?? 'application/octet-stream');
    }

    /**
     * Builds a normalized list of `File` objects from the `$_FILES` entry
     * identified by `$key`.
     *
     * Both the single-file shape (`<input type="file" name="key">`) and the
     * multi-file shape (`name="key[]"`) are supported. Entries reported with
     * `UPLOAD_ERR_NO_FILE` (an empty file input) are skipped. Other upload
     * errors are preserved on the returned object and surface when the adapter
     * validates the file.
     *
     * @param string $key The `$_FILES` key to read.
     * @return File[] The normalized files, possibly empty.
     */
    public static function setPost(string $key): array
    {
        $field = $_FILES[$key] ?? null;
        if (!\is_array($field)) {
            return [];
        }
        if (!isset($field['name'], $field['tmp_name'], $field['size'], $field['type'])) {
            return [];
        }

        if (!\is_array($field['name'])) {
            $file = self::fromRow($field);

            return $file === null ? [] : [$file];
        }

        $results = [];
        foreach (array_keys($field['name']) as $index) {
            $file = self::fromRow([
                'name'     => self::indexValue($field, 'name', $index),
                'tmp_name' => self::indexValue($field, 'tmp_name', $index),
                'size'     => self::indexValue($field, 'size', $index),
                'type'     => self::indexValue($field, 'type', $index),
                'error'    => self::indexValue($field, 'error', $index),
            ]);
            if ($file !== null) {
                $results[] = $file;
            }
        }

        return $results;
    }

    /**
     * Builds a single `File` from one normalized `$_FILES` row, or null when
     * the row reports `UPLOAD_ERR_NO_FILE`.
     *
     * @param array<array-key, mixed> $row
     * @return self|null
     */
    private static function fromRow(array $row): ?self
    {
        $error = self::asInt($row['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new self(
            self::asString($row['tmp_name'] ?? ''),
            self::asString($row['name'] ?? ''),
            self::asInt($row['size'] ?? 0),
            self::asString($row['type'] ?? ''),
            $error
        );
    }

    /**
     * Reads `$field[$key][$index]` defensively, returning null when the column
     * is missing or not an array.
     *
     * @param array<array-key, mixed> $field
     * @param string                  $key
     * @param array-key               $index
     * @return mixed
     */
    private static function indexValue(array $field, string $key, $index)
    {
        $values = $field[$key] ?? null;

        return \is_array($values) ? ($values[$index] ?? null) : null;
    }

    /**
     * Coerces a value to a string, returning an empty string for non-scalars.
     *
     * @param mixed $value
     * @return string
     */
    private static function asString($value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    /**
     * Coerces a value to an int, returning 0 for non-numeric values.
     *
     * @param mixed $value
     * @return int
     */
    private static function asInt($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Loads a single existing file from the local filesystem.
     *
     * @param string $path Path to a readable file.
     * @return self
     * @throws UploadInvalidArgumentException When `$path` is empty.
     */
    public static function setPath(string $path): self
    {
        if (trim($path) === '') {
            throw new UploadInvalidArgumentException('The file path cannot be empty.');
        }

        return new self($path);
    }

    /**
     * Returns the original client file name (e.g. `holiday.JPG`).
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the target name set with {@see rename()}, or null if none was set.
     */
    public function getReName(): ?string
    {
        return $this->rename;
    }

    /**
     * Sets the name the file should be stored as, automatically appending the
     * original extension when one exists.
     *
     * @param string $rename The new base name, without extension.
     * @return $this
     */
    public function rename(string $rename): self
    {
        $this->rename = $rename
            . ($this->extension !== '' ? '.' . $this->extension : '');

        return $this;
    }

    /**
     * Stores the public URL the file is reachable at once uploaded. Adapters
     * call this after a successful transfer.
     *
     * @param string $url
     * @return $this
     */
    public function setURL(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Returns the public URL assigned by the adapter, or null if the file has
     * not been stored yet.
     */
    public function getURL(): ?string
    {
        return $this->url;
    }

    /**
     * Returns the canonical absolute path to the source file, falling back to
     * the raw path when it cannot be resolved (e.g. the file does not exist).
     */
    public function getRealPath(): string
    {
        $real = realpath($this->path);

        return $real === false ? $this->path : $real;
    }

    /**
     * Returns the raw source path exactly as supplied to the constructor.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the declared MIME type. For HTTP uploads this is the
     * client-supplied value and must not be trusted for validation; use
     * {@see getRealMimeType()} instead.
     */
    public function getMimeType(): string
    {
        return $this->type;
    }

    /**
     * Returns the MIME type detected from the file contents with `finfo`,
     * falling back to the declared type when detection fails. The result is
     * cached after the first call.
     */
    public function getRealMimeType(): string
    {
        if ($this->realType === null) {
            $this->realType = $this->detectMimeType($this->path) ?? $this->type;
        }

        return $this->realType;
    }

    /**
     * Returns the lower-cased file extension without the leading dot, derived
     * from the original file name. Returns an empty string when the name has
     * no extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Returns the file size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Returns the PHP `UPLOAD_ERR_*` code associated with the file.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Whether the file uploaded without error (`UPLOAD_ERR_OK`).
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Whether the source path is a genuine HTTP POST upload, as reported by
     * `is_uploaded_file()`. Adapters use this to decide between
     * `move_uploaded_file()` and a plain copy.
     */
    public function isUploaded(): bool
    {
        return is_uploaded_file($this->path);
    }

    /**
     * Detects the file size, returning 0 when the path is not readable.
     */
    private function detectSize(string $path): int
    {
        if (!file_exists($path)) {
            return 0;
        }
        $size = filesize($path);

        return $size === false ? 0 : $size;
    }

    /**
     * Detects the MIME type from the file contents, returning null when the
     * path is not readable or detection fails.
     */
    private function detectMimeType(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        $type = mime_content_type($path);

        return $type === false ? null : $type;
    }
}
