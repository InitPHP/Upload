<?php

/**
 * Minimal stand-ins for the parts of `aws/aws-sdk-php` that
 * {@see \InitPHP\Upload\Adapters\S3Adapter} touches.
 *
 * The real SDK is an optional (suggested) dependency, so it is not installed in
 * this package's dev environment. These stubs let the S3 adapter be statically
 * analysed and unit-tested without it. They are loaded only by the test
 * bootstrap and the PHPStan configuration, never shipped, and are skipped
 * entirely when the genuine SDK is present.
 */

declare(strict_types=1);

namespace Aws {
    if (!\class_exists(Result::class, false)) {
        /**
         * Stub of `Aws\Result` exposing only the `get()` accessor used by the
         * S3 adapter to read the stored object's public URL.
         */
        class Result
        {
            /** @var array<string, mixed> */
            private array $data;

            /**
             * @param array<string, mixed> $data
             */
            public function __construct(array $data = [])
            {
                $this->data = $data;
            }

            /**
             * @param string $key
             * @return mixed
             */
            public function get(string $key)
            {
                return $this->data[$key] ?? null;
            }
        }
    }
}

namespace Aws\S3 {
    use Aws\Result;

    if (!\class_exists(S3Client::class, false)) {
        /**
         * Stub of `Aws\S3\S3Client` exposing only the `putObject()` operation
         * used by the S3 adapter. Tests subclass this to record calls.
         */
        class S3Client
        {
            /**
             * @param array<string, mixed> $args
             */
            public function __construct(array $args = [])
            {
            }

            /**
             * @param array<string, mixed> $args
             * @return Result
             */
            public function putObject(array $args): Result
            {
                return new Result();
            }
        }
    }
}
