<?php

declare(strict_types=1);

namespace InitPHP\Upload\Tests\Support;

use InitPHP\Upload\Adapters\BaseUploadAdapter;

/**
 * A concrete {@see BaseUploadAdapter} used to exercise the shared base
 * behaviour (validation, option/credential handling, target-name building) in
 * isolation, and to observe how {@see \InitPHP\Upload\Upload} forwards calls.
 */
final class TestAdapter extends BaseUploadAdapter
{
    /**
     * Names of the forwarding methods invoked on this instance, in order.
     *
     * @var list<string>
     */
    public array $calls = [];

    public function with(): self
    {
        $this->calls[] = 'with';

        return clone $this;
    }

    public function to($target = null)
    {
        $this->calls[] = 'to:' . var_export($target, true);

        return false;
    }

    /**
     * Exposes the protected {@see BaseUploadAdapter::checkFile()} for testing.
     */
    public function exposeCheckFile(): void
    {
        $this->checkFile();
    }

    /**
     * Exposes the protected {@see BaseUploadAdapter::targetName()} for testing.
     *
     * @param string|null $target
     */
    public function exposeTargetName($target): string
    {
        return $this->targetName($target);
    }

    /**
     * @return array<string, mixed>
     */
    public function exposeOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, mixed>
     */
    public function exposeCredentials(): array
    {
        return $this->credentials;
    }

    public function exposeStringCredential(string $key, string $default = ''): string
    {
        return $this->stringCredential($key, $default);
    }

    public function exposeIntCredential(string $key, int $default = 0): int
    {
        return $this->intCredential($key, $default);
    }

    public function exposeBoolCredential(string $key, bool $default = false): bool
    {
        return $this->boolCredential($key, $default);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeArrayOption(string $key): array
    {
        return $this->arrayOption($key);
    }

    public function exposeIntOption(string $key, int $default = 0): int
    {
        return $this->intOption($key, $default);
    }
}
