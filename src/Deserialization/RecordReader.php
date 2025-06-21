<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class RecordReader implements ReaderInterface
{
    /**
     * @param array<RecordPropertyReader> $propertyReaders
     */
    public function __construct(
        private readonly array $propertyReaders,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $record = [];
        foreach ($this->propertyReaders as $propertyReader) {
            $propertyReader->read($stream, $record);
        }

        return $record;
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        foreach ($this->propertyReaders as $propertyReader) {
            $propertyReader->skip($stream);
        }
    }
}
