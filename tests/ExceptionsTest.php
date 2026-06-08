<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests;

use InitPHP\Upload\Exceptions\UnsupportedException;
use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\Exceptions\UploadInvalidArgumentException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

use function get_parent_class;

final class ExceptionsTest extends TestCase
{
    public function testUploadExceptionExtendsRuntimeException(): void
    {
        self::assertTrue((new ReflectionClass(UploadException::class))->isSubclassOf(RuntimeException::class));
    }

    public function testUnsupportedExceptionExtendsUploadException(): void
    {
        self::assertTrue((new ReflectionClass(UnsupportedException::class))->isSubclassOf(UploadException::class));
    }

    public function testUnsupportedExceptionIsCatchableAsUploadException(): void
    {
        $caught = null;
        try {
            throw new UnsupportedException('nope');
        } catch (UploadException $e) {
            $caught = $e;
        }

        self::assertInstanceOf(UnsupportedException::class, $caught);
        self::assertSame('nope', $caught->getMessage());
    }

    public function testInvalidArgumentExceptionExtendsSplInvalidArgumentNotUploadException(): void
    {
        // Its direct parent is the SPL exception, so it sits outside the
        // UploadException hierarchy and signals programmer error instead.
        self::assertSame(InvalidArgumentException::class, get_parent_class(UploadInvalidArgumentException::class));
    }

    public function testInvalidArgumentExceptionIsCatchableAsSplInvalidArgument(): void
    {
        $caught = null;
        try {
            throw new UploadInvalidArgumentException('bad path');
        } catch (InvalidArgumentException $e) {
            $caught = $e;
        }

        self::assertInstanceOf(UploadInvalidArgumentException::class, $caught);
        self::assertSame('bad path', $caught->getMessage());
    }
}
