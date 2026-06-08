<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Exceptions\UploadInvalidArgumentException;
use InitPHP\Upload\File;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

final class FileTest extends UploadTestCase
{
    public function testNameDefaultsToBaseNameOfPath(): void
    {
        $file = new File('/tmp/path/to/document.pdf');

        self::assertSame('document.pdf', $file->getName());
    }

    public function testNameUsesProvidedClientName(): void
    {
        $file = new File('/tmp/php7F2A', 'holiday.jpg');

        self::assertSame('holiday.jpg', $file->getName());
    }

    /**
     * Regression test: the extension must come from the original client name,
     * not from the (extension-less) upload temp path.
     */
    public function testExtensionIsDerivedFromClientNameNotTempPath(): void
    {
        $file = new File('/tmp/phpUPLOAD', 'holiday.JPG');

        self::assertSame('jpg', $file->getExtension());
    }

    public function testExtensionIsLowerCased(): void
    {
        $file = new File('/tmp/x', 'ARCHIVE.TAR.GZ');

        self::assertSame('gz', $file->getExtension());
    }

    public function testExtensionIsEmptyWhenNameHasNone(): void
    {
        $file = new File('/tmp/x', 'README');

        self::assertSame('', $file->getExtension());
    }

    public function testGivenSizeIsUsedVerbatim(): void
    {
        $file = new File('/tmp/x', 'a.txt', 1234);

        self::assertSame(1234, $file->getSize());
    }

    public function testSizeIsDetectedFromTheFileWhenNotGiven(): void
    {
        $path = $this->createFile('0123456789');

        $file = new File($path, 'a.txt');

        self::assertSame(10, $file->getSize());
    }

    public function testSizeIsZeroForMissingFileInsteadOfThrowing(): void
    {
        $file = new File('/no/such/file', 'ghost.txt');

        self::assertSame(0, $file->getSize());
    }

    public function testGivenMimeTypeIsUsedVerbatim(): void
    {
        $file = new File('/tmp/x', 'a.txt', null, 'image/png');

        self::assertSame('image/png', $file->getMimeType());
    }

    public function testMimeTypeFallsBackToOctetStreamForMissingFile(): void
    {
        $file = new File('/no/such/file', 'ghost.bin');

        self::assertSame('application/octet-stream', $file->getMimeType());
    }

    public function testGetRealMimeTypeDetectsFromContentsWithoutMutatingDeclaredType(): void
    {
        $path = $this->createFile('plain text body', 'note.txt');

        $file = new File($path, 'note.txt', null, 'application/x-declared');

        self::assertSame('text/plain', $file->getRealMimeType());
        self::assertSame('application/x-declared', $file->getMimeType());
    }

    public function testGetRealPathResolvesAnExistingFile(): void
    {
        $path = $this->createFile('data', 'real.txt');

        $file = new File($path, 'real.txt');

        self::assertSame(realpath($path), $file->getRealPath());
    }

    public function testGetRealPathFallsBackToRawPathWhenItCannotBeResolved(): void
    {
        $file = new File('/no/such/file', 'ghost.txt');

        self::assertSame('/no/such/file', $file->getRealPath());
        self::assertSame('/no/such/file', $file->getPath());
    }

    public function testRenameAppendsTheOriginalExtension(): void
    {
        $file = new File('/tmp/phpUP', 'holiday.jpg');

        $file->rename('avatar');

        self::assertSame('avatar.jpg', $file->getReName());
    }

    public function testRenameWithoutAnExtensionLeavesNameBare(): void
    {
        $file = new File('/tmp/x', 'README');

        $file->rename('LICENSE');

        self::assertSame('LICENSE', $file->getReName());
    }

    public function testGetReNameIsNullByDefault(): void
    {
        $file = new File('/tmp/x', 'a.txt');

        self::assertNull($file->getReName());
    }

    public function testSetAndGetUrl(): void
    {
        $file = new File('/tmp/x', 'a.txt');

        $file->setURL('https://cdn.example.com/a.txt');

        self::assertSame('https://cdn.example.com/a.txt', $file->getURL());
    }

    public function testGetUrlIsNullByDefault(): void
    {
        $file = new File('/tmp/x', 'a.txt');

        self::assertNull($file->getURL());
    }

    public function testErrorDefaultsToOkAndFileIsValid(): void
    {
        $file = new File('/tmp/x', 'a.txt');

        self::assertSame(UPLOAD_ERR_OK, $file->getError());
        self::assertTrue($file->isValid());
    }

    public function testErrorIsPreservedAndMarksFileInvalid(): void
    {
        $file = new File('/tmp/x', 'a.txt', 0, null, UPLOAD_ERR_INI_SIZE);

        self::assertSame(UPLOAD_ERR_INI_SIZE, $file->getError());
        self::assertFalse($file->isValid());
    }

    public function testIsUploadedIsFalseForARegularFile(): void
    {
        $path = $this->createFile('data', 'regular.txt');

        $file = new File($path, 'regular.txt');

        self::assertFalse($file->isUploaded());
    }

    public function testSetPathLoadsAFile(): void
    {
        $path = $this->createFile('data', 'loaded.txt');

        $file = File::setPath($path);

        self::assertSame('loaded.txt', $file->getName());
        self::assertSame('txt', $file->getExtension());
    }

    public function testSetPathRejectsAnEmptyPath(): void
    {
        $this->expectException(UploadInvalidArgumentException::class);

        File::setPath('   ');
    }
}
