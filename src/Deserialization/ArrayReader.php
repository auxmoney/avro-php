<?php

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class ArrayReader implements ReaderInterface
{
    public function __construct(
        private readonly ReaderInterface $itemReader,
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $length = $this->decoder->readLong($stream);
        $items = [];
        for ($i = 0; $i < $length; ++$i) {
            $items[] = $this->itemReader->read($stream);
        }

        return $items;
    }
}