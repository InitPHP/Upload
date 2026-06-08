<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;
use InitPHP\Upload\Tests\Support\TestableS3Adapter;

final class S3AdapterTest extends UploadTestCase
{
    /**
     * @param array<string, mixed> $credentials
     */
    private function adapter(array $credentials = []): TestableS3Adapter
    {
        return new TestableS3Adapter(array_merge([
            'key'        => 'access-key',
            'secret_key' => 'secret',
            'region'     => 'eu-central-1',
            'bucket'     => 'my-bucket',
        ], $credentials));
    }

    public function testUsesBucketFromCredentialsAndTargetAsKeyPrefix(): void
    {
        $adapter = $this->adapter();

        $result = $adapter->setFile(new File('/tmp/x', 'source.txt'))->to('avatars');

        self::assertInstanceOf(File::class, $result);
        self::assertSame('my-bucket', $adapter->recordingClient->lastArgs['Bucket']);
        self::assertSame('avatars/source.txt', $adapter->recordingClient->lastArgs['Key']);
    }

    public function testKeyHasNoPrefixWithoutATarget(): void
    {
        $adapter = $this->adapter();

        $adapter->setFile(new File('/tmp/x', 'source.txt'))->to();

        self::assertSame('source.txt', $adapter->recordingClient->lastArgs['Key']);
    }

    public function testAppliesTheConfiguredAcl(): void
    {
        $adapter = $this->adapter(['ACL' => 'private']);

        $adapter->setFile(new File('/tmp/x', 'source.txt'))->to();

        self::assertSame('private', $adapter->recordingClient->lastArgs['ACL']);
    }

    public function testReturnsTheFileWithItsObjectUrlOnSuccess(): void
    {
        $adapter = $this->adapter();
        $adapter->recordingClient->objectUrl = 'https://my-bucket.s3.amazonaws.com/avatars/source.txt';

        $result = $adapter->setFile(new File('/tmp/x', 'source.txt'))->to('avatars');

        self::assertInstanceOf(File::class, $result);
        self::assertSame('https://my-bucket.s3.amazonaws.com/avatars/source.txt', $result->getURL());
    }

    public function testReturnsFalseWhenTheResultHasNoObjectUrl(): void
    {
        $adapter = $this->adapter();
        $adapter->recordingClient->objectUrl = null;

        $result = $adapter->setFile(new File('/tmp/x', 'source.txt'))->to();

        self::assertFalse($result);
    }

    public function testAppliesRenameToTheObjectKey(): void
    {
        $adapter = $this->adapter();
        $file = (new File('/tmp/x', 'source.txt'))->rename('avatar');

        $adapter->setFile($file)->to('users');

        self::assertSame('users/avatar.txt', $adapter->recordingClient->lastArgs['Key']);
    }

    public function testValidationErrorsPropagateOutOfTo(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('The file to be uploaded is undefined.');

        $this->adapter()->to();
    }

    public function testWithReturnsAnIndependentClone(): void
    {
        $adapter = $this->adapter();

        $clone = $adapter->with();

        self::assertNotSame($adapter, $clone);
    }
}
