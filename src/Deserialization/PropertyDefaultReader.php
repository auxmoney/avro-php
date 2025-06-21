<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class PropertyDefaultReader implements RecordPropertyReader
{
    public function __construct(
        private readonly mixed $defaultValue,
        private readonly string $name,
    ) {
    }

    public function read(ReadableStreamInterface $stream, array &$record): void
    {
        $record[$this->name] = $this->defaultValue;
    }

    public function skip(ReadableStreamInterface $stream): void
    {
    }
}
