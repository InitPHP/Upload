<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests\Support;

use InitPHP\Upload\Adapters\FTPAdapter;

/**
 * An {@see FTPAdapter} whose network seams are replaced with in-memory
 * recorders, so the transfer orchestration (target-name building, directory
 * preparation, URL assignment, failure handling) can be tested without a live
 * FTP server. The real connection is never opened because both seams are
 * overridden.
 */
final class TestableFTPAdapter extends FTPAdapter
{
    /**
     * Remote names passed to {@see upload()}.
     *
     * @var list<string>
     */
    public array $uploadedNames = [];

    /**
     * Remote names passed to {@see prepareDirectory()}.
     *
     * @var list<string>
     */
    public array $preparedFor = [];

    /** Value the stubbed {@see upload()} returns. */
    public bool $uploadResult = true;

    protected function prepareDirectory(string $remoteName): void
    {
        $this->preparedFor[] = $remoteName;
    }

    protected function upload(string $remoteName, string $localPath): bool
    {
        $this->uploadedNames[] = $remoteName;

        return $this->uploadResult;
    }
}
