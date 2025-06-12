<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;

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