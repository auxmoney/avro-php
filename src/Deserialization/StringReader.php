<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class StringReader implements ReaderInterface
{
    public function __construct(
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $length = $this->decoder->readLong($stream);

        return $length === 0 ? '' : $stream->read($length);
    }
}
