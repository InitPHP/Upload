<?php

/**
 * Upload.php
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

use InitPHP\Upload\Adapters\BaseUploadAdapter;
use InitPHP\Upload\Interfaces\UploadAdapterInterface;

/**
 * Thin, type-safe decorator over a storage adapter.
 *
 * `Upload` forwards each call to the wrapped adapter. The mutating `set*`
 * methods return the same `Upload` instance for fluent chaining, while the
 * `with*` methods return a new `Upload` wrapping a fresh adapter clone, so the
 * original is never modified.
 */
final class Upload implements UploadAdapterInterface
{
    private BaseUploadAdapter $adapter;

    /**
     * @param BaseUploadAdapter $adapter The storage adapter to wrap.
     */
    public function __construct(BaseUploadAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns the wrapped adapter.
     *
     * @return BaseUploadAdapter
     */
    public function getAdapter(): BaseUploadAdapter
    {
        return $this->adapter;
    }

    /**
     * {@inheritDoc}
     */
    public function with(): self
    {
        return new self($this->adapter->with());
    }

    /**
     * {@inheritDoc}
     */
    public function setOption(string $name, $value): self
    {
        $this->adapter->setOption($name, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options): self
    {
        $this->adapter->setOptions($options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withOption(string $name, $value): self
    {
        return new self($this->adapter->withOption($name, $value));
    }

    /**
     * {@inheritDoc}
     */
    public function withOptions(array $options): self
    {
        return new self($this->adapter->withOptions($options));
    }

    /**
     * {@inheritDoc}
     */
    public function setCredentials(array $credentials): self
    {
        $this->adapter->setCredentials($credentials);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withCredentials(array $credentials): self
    {
        return new self($this->adapter->withCredentials($credentials));
    }

    /**
     * {@inheritDoc}
     */
    public function setFile(File $file): self
    {
        $this->adapter->setFile($file);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function to($target = null)
    {
        return $this->adapter->to($target);
    }
}
