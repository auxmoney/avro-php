<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class FloatReader implements ReaderInterface
{
    public function __construct(
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        return $this->decoder->readFloat($stream);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $stream->skip(4);
    }
}
