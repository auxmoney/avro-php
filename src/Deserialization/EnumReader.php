<?php

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use RuntimeException;

class EnumReader implements ReaderInterface
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private readonly array $values,
        private readonly BinaryDecoder $decoder
    ) {
    }

    public function read(ReadableStreamInterface $stream): string
    {
        $index = $this->decoder->readLong($stream);
        if (!isset($this->values[$index])) {
            throw new RuntimeException('Invalid enum index: ' . $index);
        }

        return $this->values[$index];
    }
}