<?php

/**
 * FTPAdapter.php
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

use InitPHP\Upload\Exceptions\UnsupportedException;
use InitPHP\Upload\Exceptions\UploadException;
use Throwable;

use function array_merge;
use function explode;
use function fclose;
use function fopen;
use function ftp_chdir;
use function ftp_close;
use function ftp_connect;
use function ftp_fput;
use function ftp_login;
use function ftp_mkdir;
use function ftp_pasv;
use function ftp_ssl_connect;
use function rtrim;
use function str_replace;
use function strrpos;
use function substr;
use function trim;

use const FTP_BINARY;

/**
 * Uploads files to a remote server over FTP (or FTPS).
 *
 * Transfers use binary mode so non-text files are not corrupted, passive mode
 * is enabled by default for NAT/firewall friendliness, and the remote
 * directory implied by `$target` is created on a best-effort basis. The
 * connection is opened lazily and closed on destruction.
 */
class FTPAdapter extends BaseUploadAdapter
{
    /**
     * FTP credentials.
     *
     * - `host`/`port`/`username`/`password`/`timeout`: connection details.
     * - `url`: public base URL the remote root is served from.
     * - `passive`: whether to enable passive mode (default true).
     * - `ssl`: whether to open an explicit FTPS connection (default false).
     *
     * @var array{host: string, port: int, username: string, password: string, timeout: int, url: string, passive: bool, ssl: bool}
     */
    protected const CREDENTIALS = [
        'host'     => '',
        'port'     => 21,
        'username' => '',
        'password' => '',
        'timeout'  => 90,
        'url'      => '',
        'passive'  => true,
        'ssl'      => false,
    ];

    /** @var \FTP\Connection|null */
    private $connection;

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $options
     * @throws UnsupportedException When the `ftp` extension is not loaded.
     */
    public function __construct(array $credentials, array $options = [])
    {
        if (!\extension_loaded('ftp')) {
            throw new UnsupportedException('FTP adapter cannot be used on your server.');
        }
        parent::__construct(array_merge(self::CREDENTIALS, $credentials), $options);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritDoc}
     */
    public function with(): self
    {
        return (clone $this)->close();
    }

    /**
     * {@inheritDoc}
     */
    public function to($target = null)
    {
        try {
            $this->checkFile();

            $name = $this->targetName($target);

            $this->prepareDirectory($name);

            if (!$this->upload($name, $this->file->getRealPath())) {
                return false;
            }

            $url = rtrim($this->stringCredential('url'), '\\/') . '/' . trim(str_replace('\\', '/', $name), '/');
            $this->file->setURL($url);

            return $this->file;
        } catch (UploadException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UploadException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Streams the local file to the remote server in binary mode.
     *
     * @param string $remoteName Remote destination path.
     * @param string $localPath  Local source path.
     * @return bool Whether the transfer succeeded.
     */
    protected function upload(string $remoteName, string $localPath): bool
    {
        $resource = fopen($localPath, 'rb');
        if ($resource === false) {
            return false;
        }
        try {
            return ftp_fput($this->getConnection(), $remoteName, $resource, FTP_BINARY);
        } finally {
            fclose($resource);
        }
    }

    /**
     * Opens (once) and returns the authenticated FTP connection, applying the
     * configured passive/SSL settings.
     *
     * @return \FTP\Connection
     * @throws UploadException When connecting or authenticating fails.
     */
    protected function getConnection()
    {
        try {
            if (isset($this->connection)) {
                return $this->connection;
            }

            $host = $this->stringCredential('host');
            $port = $this->intCredential('port', 21);
            $timeout = $this->intCredential('timeout', 90);

            $connection = $this->boolCredential('ssl')
                ? ftp_ssl_connect($host, $port, $timeout)
                : ftp_connect($host, $port, $timeout);

            if ($connection === false) {
                throw new UploadException('FTP connection failed.');
            }
            if (!ftp_login($connection, $this->stringCredential('username'), $this->stringCredential('password'))) {
                ftp_close($connection);

                throw new UploadException('FTP username or password incorrect!');
            }

            ftp_pasv($connection, $this->boolCredential('passive', true));

            return $this->connection = $connection;
        } catch (UploadException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UploadException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Creates the directories implied by a remote file path, one segment at a
     * time, ignoring segments that already exist.
     *
     * @param string $remoteName The full remote file path.
     * @return void
     */
    protected function prepareDirectory(string $remoteName): void
    {
        $directory = trim(str_replace('\\', '/', $remoteName), '/');
        $position = strrpos($directory, '/');
        if ($position === false) {
            return;
        }
        $directory = substr($directory, 0, $position);

        $connection = $this->getConnection();
        $path = '';
        foreach (explode('/', $directory) as $segment) {
            if ($segment === '') {
                continue;
            }
            $path .= '/' . $segment;
            if (@ftp_chdir($connection, $path)) {
                continue;
            }
            @ftp_mkdir($connection, $path);
        }
        @ftp_chdir($connection, '/');
    }

    /**
     * Closes the FTP connection if one is open.
     *
     * @return $this
     */
    protected function close(): self
    {
        if (isset($this->connection)) {
            ftp_close($this->connection);
            $this->connection = null;
        }

        return $this;
    }
}
