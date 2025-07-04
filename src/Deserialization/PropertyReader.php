<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

readonly class PropertyReader implements RecordPropertyReader
{
    public function __construct(
        public ReaderInterface $typeReader,
        public string $name,
        public bool $hasDefault,
        public mixed $default,
    ) {
    }

    public function read(ReadableStreamInterface $stream, array &$record): void
    {
        $value = $this->typeReader->read($stream);
        if ($value === null && $this->hasDefault) {
            $record[$this->name] = $this->default;
            return;
        }

        $record[$this->name] = $value;
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->typeReader->skip($stream);
    }
}
