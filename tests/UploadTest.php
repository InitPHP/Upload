<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\File;
use InitPHP\Upload\Interfaces\UploadAdapterInterface;
use InitPHP\Upload\Tests\Support\TestAdapter;
use InitPHP\Upload\Upload;
use PHPUnit\Framework\TestCase;

use function class_implements;

final class UploadTest extends TestCase
{
    private function upload(?TestAdapter $adapter = null): Upload
    {
        return new Upload($adapter ?? new TestAdapter([], []));
    }

    public function testImplementsTheAdapterInterface(): void
    {
        self::assertContains(UploadAdapterInterface::class, class_implements($this->upload()));
    }

    public function testGetAdapterReturnsTheWrappedAdapter(): void
    {
        $adapter = new TestAdapter([], []);

        self::assertSame($adapter, (new Upload($adapter))->getAdapter());
    }

    public function testSetFileReturnsTheSameUploadAndForwards(): void
    {
        $upload = $this->upload();

        $returned = $upload->setFile(new File('/tmp/x', 'a.txt'));

        self::assertSame($upload, $returned);
    }

    public function testSetOptionForwardsToTheAdapterAndChains(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $returned = $upload->setOption('allowed_max_size', 1024);

        self::assertSame($upload, $returned);
        self::assertSame(1024, $adapter->exposeOptions()['allowed_max_size']);
    }

    public function testSetOptionsForwardsToTheAdapter(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $returned = $upload->setOptions(['allowed_max_size' => 2048, 'allowed_extensions' => ['png']]);

        self::assertSame($upload, $returned);
        self::assertSame(2048, $adapter->exposeOptions()['allowed_max_size']);
        self::assertSame(['png'], $adapter->exposeOptions()['allowed_extensions']);
    }

    public function testSetCredentialsForwardsToTheAdapter(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $upload->setCredentials(['host' => 'ftp.example.com']);

        self::assertSame('ftp.example.com', $adapter->exposeCredentials()['host']);
    }

    public function testWithOptionsReturnsANewUploadWithoutMutatingTheOriginal(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $new = $upload->withOptions(['allowed_max_size' => 4096]);

        self::assertNotSame($upload, $new);
        self::assertNotSame($adapter, $new->getAdapter());
        self::assertContains('with', $adapter->calls);
        self::assertSame(0, $adapter->exposeOptions()['allowed_max_size']);
    }

    public function testWithCredentialsReturnsANewUploadWithoutMutatingTheOriginal(): void
    {
        $adapter = new TestAdapter(['host' => 'original'], []);
        $upload = new Upload($adapter);

        $new = $upload->withCredentials(['host' => 'changed']);

        self::assertNotSame($upload, $new);
        self::assertNotSame($adapter, $new->getAdapter());
        self::assertContains('with', $adapter->calls);
        self::assertSame('original', $adapter->exposeCredentials()['host']);
    }

    public function testWithReturnsANewUploadWrappingAClone(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $clone = $upload->with();

        self::assertNotSame($upload, $clone);
        self::assertNotSame($adapter, $clone->getAdapter());
        self::assertContains('with', $adapter->calls);
    }

    public function testWithOptionReturnsANewUploadWithoutMutatingTheOriginal(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);

        $new = $upload->withOption('allowed_max_size', 99);

        self::assertNotSame($upload, $new);
        // The original adapter is cloned (not mutated) before the option is set.
        self::assertNotSame($adapter, $new->getAdapter());
        self::assertContains('with', $adapter->calls);
        self::assertSame(0, $adapter->exposeOptions()['allowed_max_size']);
    }

    public function testToDelegatesToTheAdapterWithItsTarget(): void
    {
        $adapter = new TestAdapter([], []);
        $upload = new Upload($adapter);
        $upload->setFile(new File('/tmp/x', 'a.txt'));

        $result = $upload->to('folder');

        self::assertFalse($result);
        self::assertContains("to:'folder'", $adapter->calls);
    }
}
