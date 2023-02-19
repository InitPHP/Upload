<?php
/**
 * FTPAdapter.php
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

use InitPHP\Upload\Exceptions\UnsupportedException;
use InitPHP\Upload\Exceptions\UploadException;

class FTPAdapter extends BaseUploadAdapter
{

    protected const CREDENTIALS = [
        'host'      => '',
        'port'      => 21,
        'username'  => '',
        'password'  => '',
        'timeout'   => 90,
        'url'       => '',
    ];

    /** @var resource|\FTP\Connection */
    private $connection;

    public function __construct(array $credentials, array $options = [])
    {
        if (!extension_loaded('ftp')) {
            throw new UnsupportedException('FTP adapter cannot be used on your server.');
        }
        parent::__construct(array_merge(self::CREDENTIALS, $credentials), $options);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function with(): self
    {
        return (clone $this)->close();
    }

    public function to($target = null)
    {
        try {
            $this->checkFile();
            $name = ($target !== null ? \rtrim($target, "/\\") . '/' : '')
                . ($this->file->getReName() ?? $this->file->getName());

            if ($resource = \fopen($this->file->getRealPath(), 'r')) {
                if (\ftp_fput($this->getConnection(), $name, $resource, \FTP_ASCII)) {
                    $this->file->setURL(\rtrim($this->credentials['url'], "\\/") . '/' . \trim($name, "\\/"));

                    $res = $this->file;
                } else {
                    $res = false;
                }
                \fclose($resource);
                return $res;
            }

            return false;
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    protected function getConnection()
    {
        try {
            if (!isset($this->connection)) {
                $this->connection = \ftp_connect($this->credentials['host'], $this->credentials['port'], $this->credentials['timeout']);
                if ($this->connection === FALSE) {
                    throw new UploadException('FTP connection failed.');
                }
                if (!\ftp_login($this->connection, $this->credentials['username'], $this->credentials['password'])) {
                    throw new UploadException('FTP username or password incorrect!');
                }
            }

            return $this->connection;
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    protected function close(): self
    {
        if (isset($this->connection)) {
            \ftp_close($this->connection);
            unset($this->connection);
        }

        return $this;
    }

}
