<?php

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class BinaryDecoder
{
    public function decodeLong(array $bytes): int
    {
        $b = array_shift($bytes);
        $n = $b & 0x7f;
        $shift = 7;
        while (0 != ($b & 0x80)) {
            $b = array_shift($bytes);
            $n |= (($b & 0x7f) << $shift);
            $shift += 7;
        }
        return ($n >> 1) ^ (($n >> 63) << 63) ^ -($n & 1);
    }

    public function readLong(ReadableStreamInterface $stream): int
    {
        $byte = ord($stream->read(1));
        $bytes = [$byte];
        while (0 != ($byte & 0x80)) {
            $byte = ord($stream->read(1));
            $bytes[] = $byte;
        }

        return $this->decodeLong($bytes);
    }

    public function readFloat(ReadableStreamInterface $stream): float
    {
        $float = unpack('g', $stream->read(4));
        return (float) $float[1];
    }

    public function readDouble(ReadableStreamInterface $stream): float
    {
        $double = unpack('e', $stream->read(8));
        return (float) $double[1];
    }
}