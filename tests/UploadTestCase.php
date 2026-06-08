<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Base test case that creates throwaway files and directories under the system
 * temp directory and removes them in tear-down, so tests never touch the
 * working tree or leak temp files.
 */
abstract class UploadTestCase extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->deleteTree($dir);
        }
        $this->tempDirs = [];

        parent::tearDown();
    }

    /**
     * Creates an empty temp directory and returns its path.
     */
    protected function makeDir(): string
    {
        $dir = sys_get_temp_dir() . '/upload-test-' . uniqid('', true);
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            self::fail(\sprintf('Could not create temp directory "%s".', $dir));
        }
        $this->tempDirs[] = $dir;

        return $dir;
    }

    /**
     * Writes a file with the given contents inside a fresh temp directory and
     * returns its full path.
     */
    protected function createFile(string $contents = 'payload', string $filename = 'source.txt'): string
    {
        $path = $this->makeDir() . '/' . $filename;
        file_put_contents($path, $contents);

        return $path;
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->deleteTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
