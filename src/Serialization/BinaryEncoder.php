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
        $n = (int) $value;
        $n = ($n << 1) ^ ($n >> 63);

        if ($n >= 0 && $n < 0x80) {
            return chr($n);
        }

        $buf = [];
        if (($n & ~0x7F) != 0) {
            $buf[] = ($n | 0x80) & 0xFF;
            $n = ($n >> 7) ^ (($n >> 63) << 57); // unsigned shift right ($n >>> 7)

            while ($n > 0x7F) {
                $buf[] = ($n | 0x80) & 0xFF;
                $n >>= 7; // $n is always positive here
            }
        }

        $buf[] = $n;
        return pack('C*', ...$buf);
    }
}
