<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;
use InitPHP\Upload\Tests\Support\TestAdapter;

use const UPLOAD_ERR_CANT_WRITE;

final class ValidationTest extends UploadTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    private function adapter(array $options = []): TestAdapter
    {
        return new TestAdapter([], $options);
    }

    public function testThrowsWhenNoFileIsQueued(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('The file to be uploaded is undefined.');

        $this->adapter()->exposeCheckFile();
    }

    public function testThrowsWhenTheUploadErrored(): void
    {
        $adapter = $this->adapter();
        $adapter->setFile(new File('/tmp/x', 'a.txt', 0, null, UPLOAD_ERR_CANT_WRITE));

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('error code ' . UPLOAD_ERR_CANT_WRITE);

        $adapter->exposeCheckFile();
    }

    public function testAcceptsAnAllowedExtension(): void
    {
        $adapter = $this->adapter(['allowed_extensions' => ['txt', 'md']]);
        $adapter->setFile(new File('/tmp/x', 'notes.txt'));

        $adapter->exposeCheckFile();

        $this->addToAssertionCount(1);
    }

    public function testRejectsADisallowedExtension(): void
    {
        $adapter = $this->adapter(['allowed_extensions' => ['png', 'jpg']]);
        $adapter->setFile(new File('/tmp/x', 'notes.txt'));

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('This file extension is not allowed.');

        $adapter->exposeCheckFile();
    }

    public function testExtensionMatchingIsCaseInsensitive(): void
    {
        $adapter = $this->adapter(['allowed_extensions' => ['TXT']]);
        $adapter->setFile(new File('/tmp/x', 'NOTES.Txt'));

        $adapter->exposeCheckFile();

        $this->addToAssertionCount(1);
    }

    public function testAcceptsAnAllowedMimeType(): void
    {
        $path = $this->createFile('plain body', 'note.txt');
        $adapter = $this->adapter(['allowed_mime_types' => ['text/plain']]);
        $adapter->setFile(File::setPath($path));

        $adapter->exposeCheckFile();

        $this->addToAssertionCount(1);
    }

    public function testRejectsADisallowedMimeType(): void
    {
        $path = $this->createFile('plain body', 'note.txt');
        $adapter = $this->adapter(['allowed_mime_types' => ['image/png']]);
        $adapter->setFile(File::setPath($path));

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('This file type is not allowed.');

        $adapter->exposeCheckFile();
    }

    public function testRejectsAFileExceedingTheMaxSize(): void
    {
        $adapter = $this->adapter(['allowed_max_size' => 1024]);
        $adapter->setFile(new File('/tmp/x', 'big.bin', 2048));

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Exceeds the maximum uploadable file size.');

        $adapter->exposeCheckFile();
    }

    public function testAcceptsAFileWithinTheMaxSize(): void
    {
        $adapter = $this->adapter(['allowed_max_size' => 4096]);
        $adapter->setFile(new File('/tmp/x', 'ok.bin', 2048));

        $adapter->exposeCheckFile();

        $this->addToAssertionCount(1);
    }

    public function testPassesWhenNoRestrictionsAreConfigured(): void
    {
        $adapter = $this->adapter();
        $adapter->setFile(new File('/tmp/x', 'anything.xyz', 999999));

        $adapter->exposeCheckFile();

        $this->addToAssertionCount(1);
    }

    public function testTargetNameWithoutPrefixIsJustTheFileName(): void
    {
        $adapter = $this->adapter();
        $adapter->setFile(new File('/tmp/x', 'photo.jpg'));

        self::assertSame('photo.jpg', $adapter->exposeTargetName(null));
        self::assertSame('photo.jpg', $adapter->exposeTargetName(''));
    }

    public function testTargetNamePrependsTheNormalizedPrefix(): void
    {
        $adapter = $this->adapter();
        $adapter->setFile(new File('/tmp/x', 'photo.jpg'));

        self::assertSame('avatars/photo.jpg', $adapter->exposeTargetName('avatars'));
        self::assertSame('a/b/photo.jpg', $adapter->exposeTargetName('\\a\\b\\'));
    }

    public function testTargetNameUsesTheRenamedName(): void
    {
        $file = new File('/tmp/x', 'photo.jpg');
        $file->rename('avatar');
        $adapter = $this->adapter();
        $adapter->setFile($file);

        self::assertSame('users/avatar.jpg', $adapter->exposeTargetName('users'));
    }
}
