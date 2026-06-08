<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Adapters\LocalAdapter;
use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\File;

use function file_get_contents;

final class LocalAdapterTest extends UploadTestCase
{
    public function testStoresAPathLoadedFileAndSetsUrl(): void
    {
        $source = $this->createFile('hello world', 'avatar.png');
        $dir = $this->makeDir();

        $adapter = new LocalAdapter([
            'dir' => $dir,
            'url' => 'https://cdn.example.com/uploads',
        ]);

        $result = $adapter->setFile(File::setPath($source))->to();

        self::assertInstanceOf(File::class, $result);
        self::assertFileExists($dir . '/avatar.png');
        self::assertSame('hello world', file_get_contents($dir . '/avatar.png'));
        self::assertSame('https://cdn.example.com/uploads/avatar.png', $result->getURL());
        // A path-loaded file is copied, not moved: the source survives.
        self::assertFileExists($source);
    }

    public function testCreatesNestedTargetDirectory(): void
    {
        $source = $this->createFile('data', 'doc.txt');
        $dir = $this->makeDir();

        $adapter = new LocalAdapter(['dir' => $dir, 'url' => 'https://e.test']);

        $result = $adapter->setFile(File::setPath($source))->to('2026/06');

        self::assertInstanceOf(File::class, $result);
        self::assertFileExists($dir . '/2026/06/doc.txt');
        self::assertSame('https://e.test/2026/06/doc.txt', $result->getURL());
    }

    public function testAppliesRenameToTheStoredFile(): void
    {
        $source = $this->createFile('data', 'original.txt');
        $dir = $this->makeDir();

        $adapter = new LocalAdapter(['dir' => $dir, 'url' => 'https://e.test']);
        $file = File::setPath($source)->rename('renamed');

        $result = $adapter->setFile($file)->to();

        self::assertInstanceOf(File::class, $result);
        self::assertFileExists($dir . '/renamed.txt');
        self::assertSame('https://e.test/renamed.txt', $result->getURL());
    }

    public function testReturnsFalseWhenTheTransferFails(): void
    {
        $source = $this->createFile('data', 'doc.txt');
        $dir = $this->makeDir();

        $adapter = new class (['dir' => $dir, 'url' => 'https://e.test']) extends LocalAdapter {
            protected function transfer(File $file, string $destination): bool
            {
                return false;
            }
        };

        $result = $adapter->setFile(File::setPath($source))->to();

        self::assertFalse($result);
    }

    public function testRejectsADisallowedExtensionBeforeWriting(): void
    {
        $source = $this->createFile('data', 'script.exe');
        $dir = $this->makeDir();

        $adapter = new LocalAdapter(
            ['dir' => $dir, 'url' => 'https://e.test'],
            ['allowed_extensions' => ['txt']]
        );

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('This file extension is not allowed.');

        $adapter->setFile(File::setPath($source))->to();
    }

    public function testThrowsWhenTheDestinationDirectoryCannotBeCreated(): void
    {
        // Use an existing *file* as the base "dir" so mkdir() underneath fails.
        $blocker = $this->createFile('not a dir', 'blocker');
        $dir = $blocker . '/inside';
        $source = $this->createFile('data', 'doc.txt');

        $adapter = new LocalAdapter(['dir' => $dir, 'url' => 'https://e.test']);

        $this->expectException(UploadException::class);

        $adapter->setFile(File::setPath($source))->to();
    }

    public function testWithReturnsAnIndependentClone(): void
    {
        $adapter = new LocalAdapter(['dir' => '/a', 'url' => 'https://e.test']);

        $clone = $adapter->with();

        self::assertNotSame($adapter, $clone);
    }
}
