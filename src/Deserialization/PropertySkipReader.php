<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class PropertySkipReader implements RecordPropertyReader
{
    public function __construct(
        public readonly ReaderInterface $typeReader,
    ) {
    }

    public function read(ReadableStreamInterface $stream, array &$record): void
    {
        $this->typeReader->skip($stream);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->typeReader->skip($stream);
    }
}
