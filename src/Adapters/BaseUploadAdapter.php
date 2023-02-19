<?php
/**
 * BaseUploadAdapter.php
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

namespace InitPHP\Upload\Adapters;

use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;
use InitPHP\Upload\Interfaces\UploadAdapterInterface;

abstract class BaseUploadAdapter implements UploadAdapterInterface
{

    protected const CONST_OPTIONS = [
        'allowed_extensions'    => [],
        'allowed_mime_types'    => [],
        'allowed_max_size'      => 0,
    ];

    protected array $credentials = [];

    protected array $options = [];

    protected File $file;

    public function __construct(array $credentials, array $options)
    {
        $this->setCredentials($credentials);
        $this->setOptions(\array_merge(self::CONST_OPTIONS, $options));
    }

    abstract public function with(): self;

    public function setCredentials(array $credentials): self
    {
        !empty($credentials) && $this->credentials = \array_merge($this->credentials, $credentials);

        return $this;
    }

    public function withCredentials(array $credentials): self
    {
        return $this->with()->setCredentials($credentials);
    }

    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = \array_merge($this->options, $options);

        return $this;
    }

    public function withOption(string $name, $value): self
    {
        return $this->with()->setOption($name, $value);
    }

    public function withOptions(array $options): self
    {
        return $this->with()->setOptions($options);
    }

    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param $target
     * @return File|false
     */
    abstract public function to($target = null);


    protected function checkFile()
    {
        if (!isset($this->file)) {
            throw new UploadException('The file to be uploaded is undefined.');
        }
        if (!empty($this->options['allowed_extensions']) && !\in_array($this->file->getExtension(), $this->options['allowed_extensions'])) {
            throw new UploadException('This file extension is not allowed.');
        }
        if (!empty($this->options['allowed_mime_types']) && !\in_array($this->file->getRealMimeType(), $this->options['allowed_mime_types'])) {
            throw new UploadException('This file type is not allowed.');
        }
        if (!empty($this->options['allowed_max_size']) && $this->options['allowed_max_size'] < $this->file->getSize()) {
            throw new UploadException('Exceeds the maximum uploadable file size.');
        }
    }

}
