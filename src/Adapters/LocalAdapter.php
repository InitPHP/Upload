<?php
/**
 * LocalAdapter.php
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

class LocalAdapter extends BaseUploadAdapter
{
    protected const CREDENTIALS = [
        'dir'       => '',
        'url'       => '',
    ];

    public function __construct(array $credentials, array $options = [])
    {
        parent::__construct(\array_merge(self::CREDENTIALS, $credentials), $options);
    }

    public function with(): self
    {
        return clone $this;
    }

    public function to($target = null)
    {
        try {
            $this->checkFile();

            $rename = ($target !== null ? \trim($target, "\\/") . '/' : '')
                . ($this->file->getReName() ?? $this->file->getName());

            $path = \rtrim($this->credentials['dir'], "\\/")
                . \DIRECTORY_SEPARATOR
                . $rename;

            if (\move_uploaded_file($this->file->getRealPath(), $path)) {
                $this->file->setURL(\rtrim($this->credentials['url'], "/") . "/" . \ltrim($rename, "/"));

                return $this->file;
            } else {
                return false;
            }

        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

}
