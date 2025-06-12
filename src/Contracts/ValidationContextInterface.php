<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface ValidationContextInterface
{
    public function pushContext(): void;

    public function popContext(bool $discardErrors): void;

    public function addError(string $message): void;

    public function pushPath(string $path): void;

    public function popPath(): void;
}