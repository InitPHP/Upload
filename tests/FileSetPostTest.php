<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\File;
use PHPUnit\Framework\TestCase;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class FileSetPostTest extends TestCase
{
    /** @var array<array-key, mixed> */
    private array $filesBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesBackup = $_FILES;
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_FILES = $this->filesBackup;

        parent::tearDown();
    }

    public function testReturnsEmptyArrayWhenKeyIsMissing(): void
    {
        self::assertSame([], File::setPost('avatar'));
    }

    public function testReturnsEmptyArrayWhenRequiredKeysAreMissing(): void
    {
        $_FILES['avatar'] = ['name' => 'a.txt'];

        self::assertSame([], File::setPost('avatar'));
    }

    public function testNormalizesASingleFileUpload(): void
    {
        $_FILES['avatar'] = [
            'name'     => 'holiday.JPG',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/phpA1',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 2048,
        ];

        $files = File::setPost('avatar');

        self::assertCount(1, $files);
        self::assertSame('holiday.JPG', $files[0]->getName());
        self::assertSame('jpg', $files[0]->getExtension());
        self::assertSame('image/jpeg', $files[0]->getMimeType());
        self::assertSame(2048, $files[0]->getSize());
    }

    public function testSingleNoFileErrorYieldsEmptyArray(): void
    {
        $_FILES['avatar'] = [
            'name'     => '',
            'type'     => '',
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_NO_FILE,
            'size'     => 0,
        ];

        self::assertSame([], File::setPost('avatar'));
    }

    public function testNormalizesAMultiFileUpload(): void
    {
        $_FILES['gallery'] = [
            'name'     => ['one.png', 'two.gif'],
            'type'     => ['image/png', 'image/gif'],
            'tmp_name' => ['/tmp/phpB1', '/tmp/phpB2'],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size'     => [100, 200],
        ];

        $files = File::setPost('gallery');

        self::assertCount(2, $files);
        self::assertSame('one.png', $files[0]->getName());
        self::assertSame('two.gif', $files[1]->getName());
        self::assertSame(200, $files[1]->getSize());
    }

    public function testSkipsNoFileEntriesWithinAMultiFileUpload(): void
    {
        $_FILES['gallery'] = [
            'name'     => ['one.png', ''],
            'type'     => ['image/png', ''],
            'tmp_name' => ['/tmp/phpC1', ''],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
            'size'     => [100, 0],
        ];

        $files = File::setPost('gallery');

        self::assertCount(1, $files);
        self::assertSame('one.png', $files[0]->getName());
    }

    public function testPreservesUploadErrorCodes(): void
    {
        $_FILES['avatar'] = [
            'name'     => 'too-big.zip',
            'type'     => 'application/zip',
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_INI_SIZE,
            'size'     => 0,
        ];

        $files = File::setPost('avatar');

        self::assertCount(1, $files);
        self::assertSame(UPLOAD_ERR_INI_SIZE, $files[0]->getError());
        self::assertFalse($files[0]->isValid());
    }
}
