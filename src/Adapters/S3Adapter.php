<?php
/**
 * S3Adapter.php
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

class S3Adapter extends BaseUploadAdapter
{
    protected const CREDENTIALS = [
        'key'           => '',
        'secret_key'    => '',
        'region'        => '',
        'bucket'        => '',
        'ACL'           => 'public-read',
        'version'       => 'latest',
    ];

    protected $client;

    public function __construct(array $credentials, array $options = [])
    {
        if (!\class_exists("\\Aws\\S3\\S3Client")) {
            throw new UnsupportedException('AWS S3 SDK must be installed to use this adapter. Try running "composer require aws/aws-sdk-php".');
        }

        parent::__construct(\array_merge(self::CREDENTIALS, $credentials), $options);
    }

    public function with(): self
    {
        $with = clone $this;
        $with->close();
        return $with;
    }

    /**
     * @inheritDoc
     */
    public function to($target = null)
    {
        try {
            $this->checkFile();
            $set = [
                'Bucket'    => $target ?? $this->credentials['bucket'],
                'Key'       => $this->file->getReName() ?? $this->file->getName(),
                'ACL'       => $this->credentials['ACL'],
            ];

            $path = $this->file->getRealPath();

            if ($resource = \fopen($path, 'r')) {
                $set['Body'] = $resource;
            } else {
                $set['SourceFile'] = $path;
            }

            $res = $this->getClient()->putObject($set);

            if ($resource !== FALSE) {
                \fclose($resource);
            }

            $url = $res->get('ObjectURL');
            if (!empty($url)) {
                $this->file->setURL($url);
                return $this->file;
            }else {
                return false;
            }
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    protected function getClient(): \Aws\S3\S3Client
    {
        try {
            if (!isset($this->client)) {
                $this->client = new \Aws\S3\S3Client([
                    'version'       => $this->credentials['version'],
                    'region'        => $this->credentials['region'],
                    'credentials'   => [
                        'key'           => $this->credentials['key'],
                        'secret'        => $this->credentials['secret_key'],
                    ],
                ]);
            }
            return $this->client;
        } catch (\Throwable $e) {
            throw new UploadException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    protected function close(): self
    {
        if (isset($this->client)) {
            unset($this->client);
        }
        return $this;
    }
}
