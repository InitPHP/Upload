<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests\Support;

use Aws\S3\S3Client;
use InitPHP\Upload\Adapters\S3Adapter;

/**
 * An {@see S3Adapter} wired to a {@see RecordingS3Client}, so the request the
 * adapter builds (bucket, key, ACL) and its handling of the result can be
 * asserted without the AWS SDK or network access.
 */
final class TestableS3Adapter extends S3Adapter
{
    public RecordingS3Client $recordingClient;

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $options
     */
    public function __construct(array $credentials, array $options = [])
    {
        parent::__construct($credentials, $options);
        $this->recordingClient = new RecordingS3Client();
    }

    protected function getClient(): S3Client
    {
        return $this->recordingClient;
    }
}
