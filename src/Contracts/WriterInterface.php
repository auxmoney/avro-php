<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface WriterInterface
{
    public function write(mixed $datum, WritableStreamInterface $stream): void;

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool;
}

interface ValidationContextInterface
{
    public function pushContext(): void;

    public function popContext(bool $discardErrors): void;

    public function addError(string $message): void;

    public function pushPath(string $path): void;

    public function popPath(): void;
}

class BooleanValidationContext implements ValidationContextInterface
{
    private array $contexts = [false];

    public function pushContext(): void
    {
        $this->contexts[] = [];
    }

    public function popContext(bool $discardErrors): void
    {
        if (count($this->contexts) === 1) {
            return;
        }

        $hasError = array_pop($this->contexts);
        if (!$discardErrors) {
            $hadError = array_pop($this->contexts);
            $this->contexts[] = $hadError || $hasError;
        }
    }

    public function addError(string $message): void
    {
        $lastIndex = count($this->contexts) - 1;
        $this->contexts[$lastIndex] = true;
    }

    public function pushPath(string $path): void
    {
    }

    public function popPath(): void
    {
    }
}

class ValidationContext implements ValidationContextInterface
{
    /** @var array<array<string>> */
    private array $errors = [[]];

    /** @var array<string> */
    private array $path = [];

    public function pushContext(): void
    {
        $this->errors[] = [];
    }

    public function popContext(bool $discardErrors): void
    {
        if (count($this->errors) === 1) {
            return;
        }

        $errors = array_pop($this->errors);
        if (!$discardErrors) {
            $this->errors = array_merge($this->errors, $errors);
        }
    }

    public function addError(string $message): void
    {
        $lastIndex = count($this->errors) - 1;

        $this->errors[$lastIndex][] = implode('.', $this->path) . ': ' . $message;
    }

    public function pushPath(string $path): void
    {
        $this->path[] = $path;
    }

    public function popPath(): void
    {
        array_pop($this->path);
    }

    public function getErrors(): array
    {
        $lastIndex = count($this->errors) - 1;

        return $this->errors[$lastIndex];
    }
}