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
    ) {
    }

    public function read(ReadableStreamInterface $stream, array &$record): void
    {
        $value = $this->typeReader->read($stream);

        $record[$this->name] = $value;
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->typeReader->skip($stream);
    }
}
