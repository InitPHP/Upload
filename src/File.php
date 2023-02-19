<?php
/**
 * File.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload;

final class File
{

    private string $path;

    private string $name;

    private int $size;

    private string $type;

    private string $extension;

    private string $url;

    private string $rename;

    public function __construct(string $path, ?string $name = null, ?int $size = null, ?string $type = null)
    {
        $this->path = $path;
        $this->name = $name ?? \basename($this->path);
        $this->extension = \pathinfo($this->path, \PATHINFO_EXTENSION);
        $this->size = $size ?? \filesize($this->path);
        $this->type = $type ?? \mime_content_type($this->path);
    }

    /**
     * @param string $key
     * @return File[]
     */
    public static function setPost(string $key): array
    {
        if (!isset($_FILES[$key])) {
            return [];
        }
        if (!isset($_FILES[$key]['name'], $_FILES[$key]['tmp_name'], $_FILES[$key]['size'], $_FILES[$key]['type'])) {
            return [];
        }
        if (!\is_array($_FILES[$key]['name'])) {
            return [new self($_FILES[$key]['tmp_name'], $_FILES[$key]['name'], $_FILES[$key]['size'], $_FILES[$key]['type'])];
        }
        $results = [];

        $indexs = array_keys($_FILES[$key]['name']);
        $keys = array_keys($_FILES[$key]);
        foreach ($indexs as $index) {
            $file = [];
            foreach ($keys as $k) {
                $file[$k] = $_FILES[$key][$k][$index];
            }
            $results[] = new self($file['tmp_name'], $file['name'], $file['size'], $file['type']);
        }
        return $results;
    }

    public static function setPath(string $path): self
    {
        return new self($path);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReName(): ?string
    {
        return $this->rename ?? null;
    }

    public function rename(string $rename): self
    {
        $this->rename = $rename
            . (!empty($this->extension) ? '.' . $this->extension : '');

        return $this;
    }

    public function setURL(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getURL(): ?string
    {
        return $this->url ?? null;
    }

    public function getRealPath(): string
    {
        return \realpath($this->path);
    }

    public function getMimeType(): string
    {
        return $this->type;
    }

    public function getRealMimeType(): string
    {
        $type = \mime_content_type($this->path);
        if ($type === FALSE) {
            return $this->type;
        }
        return $this->type = $type;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getSize(): int
    {
        return $this->size;
    }

}
