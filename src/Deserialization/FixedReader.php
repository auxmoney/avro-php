<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class FixedReader implements ReaderInterface
{
    public function __construct(
        private readonly int $size,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        return $stream->read($this->size);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $stream->skip($this->size);
    }
}
