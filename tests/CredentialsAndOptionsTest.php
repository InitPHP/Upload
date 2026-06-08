<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Tests\Support\TestAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Covers the typed credential/option accessors and the credential/option
 * merging on the base adapter.
 */
final class CredentialsAndOptionsTest extends TestCase
{
    public function testConstructorMergesDefaultOptions(): void
    {
        $adapter = new TestAdapter([], []);

        self::assertSame(
            ['allowed_extensions' => [], 'allowed_mime_types' => [], 'allowed_max_size' => 0],
            $adapter->exposeOptions()
        );
    }

    public function testSetCredentialsMergesAndIgnoresEmptyArrays(): void
    {
        $adapter = new TestAdapter(['host' => 'a'], []);
        $adapter->setCredentials([]);
        $adapter->setCredentials(['port' => 21]);

        self::assertSame(['host' => 'a', 'port' => 21], $adapter->exposeCredentials());
    }

    public function testStringCredentialReturnsValueOrDefault(): void
    {
        $adapter = new TestAdapter(['host' => 'ftp.example.com', 'flag' => ['nope']], []);

        self::assertSame('ftp.example.com', $adapter->exposeStringCredential('host'));
        self::assertSame('fallback', $adapter->exposeStringCredential('missing', 'fallback'));
        // A non-scalar value falls back to the default.
        self::assertSame('', $adapter->exposeStringCredential('flag'));
    }

    public function testIntCredentialCoercesNumericStringsAndFallsBack(): void
    {
        $adapter = new TestAdapter(['port' => '2121', 'bad' => 'xyz'], []);

        self::assertSame(2121, $adapter->exposeIntCredential('port'));
        self::assertSame(90, $adapter->exposeIntCredential('missing', 90));
        self::assertSame(90, $adapter->exposeIntCredential('bad', 90));
    }

    public function testBoolCredentialCoercesToBool(): void
    {
        $adapter = new TestAdapter(['ssl' => 1, 'passive' => 0], []);

        self::assertTrue($adapter->exposeBoolCredential('ssl'));
        self::assertFalse($adapter->exposeBoolCredential('passive'));
        self::assertTrue($adapter->exposeBoolCredential('missing', true));
    }

    public function testArrayOptionReturnsArrayOrEmpty(): void
    {
        $adapter = new TestAdapter([], ['allowed_extensions' => ['jpg', 'png'], 'allowed_max_size' => 10]);

        self::assertSame(['jpg', 'png'], $adapter->exposeArrayOption('allowed_extensions'));
        // A non-array value yields an empty array.
        self::assertSame([], $adapter->exposeArrayOption('allowed_max_size'));
        self::assertSame([], $adapter->exposeArrayOption('missing'));
    }

    public function testIntOptionCoercesAndFallsBack(): void
    {
        $adapter = new TestAdapter([], ['allowed_max_size' => '2048']);

        self::assertSame(2048, $adapter->exposeIntOption('allowed_max_size'));
        self::assertSame(5, $adapter->exposeIntOption('missing', 5));
    }
}
