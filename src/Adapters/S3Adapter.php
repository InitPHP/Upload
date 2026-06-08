<?php

/**
 * S3Adapter.php
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

use Aws\S3\S3Client;
use InitPHP\Upload\Exceptions\UnsupportedException;
use InitPHP\Upload\Exceptions\UploadException;
use Throwable;

use function array_merge;
use function class_exists;

/**
 * Uploads files to an Amazon S3 (or S3-compatible) bucket.
 *
 * Requires the `aws/aws-sdk-php` package. The bucket is taken from the
 * credentials; `$target` is used as the object key prefix, keeping the same
 * "destination path prefix" meaning it has in the other adapters.
 */
class S3Adapter extends BaseUploadAdapter
{
    /**
     * S3 credentials.
     *
     * - `key`/`secret_key`/`region`: AWS authentication and endpoint region.
     * - `bucket`: target bucket name.
     * - `ACL`: canned ACL applied to stored objects.
     * - `version`: S3 API version passed to the SDK.
     *
     * @var array{key: string, secret_key: string, region: string, bucket: string, ACL: string, version: string}
     */
    protected const CREDENTIALS = [
        'key'        => '',
        'secret_key' => '',
        'region'     => '',
        'bucket'     => '',
        'ACL'        => 'public-read',
        'version'    => 'latest',
    ];

    /** @var S3Client|null */
    protected $client;

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $options
     * @throws UnsupportedException When `aws/aws-sdk-php` is not installed.
     */
    public function __construct(array $credentials, array $options = [])
    {
        if (!class_exists(S3Client::class)) {
            throw new UnsupportedException('AWS S3 SDK must be installed to use this adapter. Try running "composer require aws/aws-sdk-php".');
        }

        parent::__construct(array_merge(self::CREDENTIALS, $credentials), $options);
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

            $result = $this->getClient()->putObject([
                'Bucket'     => $this->stringCredential('bucket'),
                'Key'        => $this->targetName($target),
                'ACL'        => $this->stringCredential('ACL', 'public-read'),
                'SourceFile' => $this->file->getRealPath(),
            ]);

            $url = $result->get('ObjectURL');
            if (!\is_string($url) || $url === '') {
                return false;
            }

            $this->file->setURL($url);

            return $this->file;
        } catch (UploadException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new UploadException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Builds (once) and returns the underlying S3 client.
     *
     * @return S3Client
     * @throws UploadException When the client cannot be created.
     */
    protected function getClient(): S3Client
    {
        try {
            if (!isset($this->client)) {
                $this->client = new S3Client([
                    'version'     => $this->stringCredential('version', 'latest'),
                    'region'      => $this->stringCredential('region'),
                    'credentials' => [
                        'key'    => $this->stringCredential('key'),
                        'secret' => $this->stringCredential('secret_key'),
                    ],
                ]);
            }

            return $this->client;
        } catch (Throwable $e) {
            throw new UploadException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Releases the cached client so a clone reconnects lazily.
     *
     * @return $this
     */
    protected function close(): self
    {
        $this->client = null;

        return $this;
    }
}
