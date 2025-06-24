<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;

class BinaryEncoder
{
    public function writeLong(WritableStreamInterface $stream, int $value): void
    {
        $stream->write($this->encodeLong($value));
    }

    public function writeString(WritableStreamInterface $stream, string $value): void
    {
        $length = strlen($value);
        $stream->write($this->encodeLong($length));
        if ($length > 0) {
            $stream->write($value);
        }
    }

    public function encodeFloat(float $float): string
    {
        return pack('g', $float);
    }

    public function encodeDouble(float $double): string
    {
        return pack('e', $double);
    }

    private function encodeLong(int $value): string
    {
        $n = ($value << 1) ^ ($value >> 63);
        $str = '';
        while (0 != ($n & ~0x7F)) {
            $str .= chr(($n & 0x7F) | 0x80);
            $n >>= 7;
        }
        $str .= chr($n);
        return $str;
    }
}
