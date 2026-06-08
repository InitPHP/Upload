<?php

/**
 * PHPUnit bootstrap.
 *
 * Loads Composer's autoloader and, when the real AWS SDK is absent, the minimal
 * `Aws\S3\S3Client` / `Aws\Result` stubs the S3 adapter tests rely on.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Stubs/aws-sdk.php';
