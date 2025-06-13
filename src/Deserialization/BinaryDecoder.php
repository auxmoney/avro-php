<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class BinaryDecoder
{
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

    public function skipLong(ReadableStreamInterface $stream): void
    {
        $byte = ord($stream->read(1));
        while (0 != ($byte & 0x80)) {
            $byte = ord($stream->read(1));
        }
    }

    public function readFloat(ReadableStreamInterface $stream): float
    {
        $float = unpack('g', $stream->read(4));
        assert(isset($float[1]) && is_float($float[1]), 'Failed to unpack float from binary data');

        return $float[1];
    }

    public function readDouble(ReadableStreamInterface $stream): float
    {
        $double = unpack('e', $stream->read(8));
        assert(isset($double[1]) && is_float($double[1]), 'Failed to unpack double from binary data');

        return $double[1];
    }

    /**
     * @param array<int> $bytes
     */
    private function decodeLong(array $bytes): int
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
}
