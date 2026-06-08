<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;
use InitPHP\Upload\Tests\Support\TestableFTPAdapter;

final class FTPAdapterTest extends UploadTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!\extension_loaded('ftp')) {
            self::markTestSkipped('The ftp extension is not loaded.');
        }
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function adapter(array $credentials = []): TestableFTPAdapter
    {
        return new TestableFTPAdapter(array_merge(['url' => 'https://files.example.com'], $credentials));
    }

    public function testUploadsWithATargetPrefixAndSetsUrl(): void
    {
        $adapter = $this->adapter();
        $file = new File('/tmp/phpFTP', 'report.pdf');

        $result = $adapter->setFile($file)->to('documents');

        self::assertInstanceOf(File::class, $result);
        self::assertSame(['documents/report.pdf'], $adapter->uploadedNames);
        self::assertSame(['documents/report.pdf'], $adapter->preparedFor);
        self::assertSame('https://files.example.com/documents/report.pdf', $result->getURL());
    }

    public function testUploadsWithoutATargetPrefix(): void
    {
        $adapter = $this->adapter();
        $file = new File('/tmp/phpFTP', 'report.pdf');

        $result = $adapter->setFile($file)->to();

        self::assertInstanceOf(File::class, $result);
        self::assertSame(['report.pdf'], $adapter->uploadedNames);
        self::assertSame('https://files.example.com/report.pdf', $result->getURL());
    }

    public function testAppliesRename(): void
    {
        $adapter = $this->adapter();
        $file = (new File('/tmp/phpFTP', 'report.pdf'))->rename('summary');

        $result = $adapter->setFile($file)->to();

        self::assertInstanceOf(File::class, $result);
        self::assertSame(['summary.pdf'], $adapter->uploadedNames);
    }

    public function testReturnsFalseWhenTheTransferFails(): void
    {
        $adapter = $this->adapter();
        $adapter->uploadResult = false;

        $result = $adapter->setFile(new File('/tmp/phpFTP', 'report.pdf'))->to();

        self::assertFalse($result);
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
