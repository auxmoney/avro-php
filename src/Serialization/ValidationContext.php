<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;

class ValidationContext implements ValidationContextInterface
{
    /** @var array<array<string>> */
    private array $contextErrors = [[]];

    /** @var array<string> */
    private array $path = [];

    public function pushContext(): void
    {
        $this->contextErrors[] = [];
    }

    public function popContext(bool $discardErrors): void
    {
        if (count($this->contextErrors) === 1) {
            return;
        }

        $errors = array_pop($this->contextErrors);
        assert(is_array($errors), 'Expected context errors to be an array');
        if (!$discardErrors) {
            $lastIndex = count($this->contextErrors) - 1;
            $this->contextErrors[$lastIndex] = array_merge($this->contextErrors[$lastIndex], $errors);
        }
    }

    public function addError(string $message): void
    {
        $lastIndex = count($this->contextErrors) - 1;

        $prefix = $this->path === [] ? '' : implode('.', $this->path) . ': ';
        $this->contextErrors[$lastIndex][] = $prefix . $message;
    }

    public function pushPath(string $path): void
    {
        $this->path[] = $path;
    }

    public function popPath(): void
    {
        array_pop($this->path);
    }

    /**
     * @return array<string>
     */
    public function getContextErrors(): array
    {
        $lastIndex = count($this->contextErrors) - 1;

        return $this->contextErrors[$lastIndex];
    }
}
