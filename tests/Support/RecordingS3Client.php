<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests\Support;

use Aws\Result;
use Aws\S3\S3Client;

/**
 * A fake `Aws\S3\S3Client` that records the arguments of the last
 * `putObject()` call and returns a configurable `ObjectURL`, so the S3 adapter
 * can be tested without contacting AWS.
 */
final class RecordingS3Client extends S3Client
{
    /**
     * Arguments passed to the most recent {@see putObject()} call.
     *
     * @var array<string, mixed>
     */
    public array $lastArgs = [];

    /** The `ObjectURL` to report; null simulates a missing URL in the result. */
    public ?string $objectUrl = 'https://example-bucket.s3.amazonaws.com/source.txt';

    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     * @return Result
     */
    public function putObject(array $args): Result
    {
        $this->lastArgs = $args;

        return new Result($this->objectUrl === null ? [] : ['ObjectURL' => $this->objectUrl]);
    }
}
