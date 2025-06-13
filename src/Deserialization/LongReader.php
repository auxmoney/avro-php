<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class LongReader implements ReaderInterface
{
    public function __construct(
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        return $this->decoder->readLong($stream);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->decoder->skipLong($stream);
    }
}
