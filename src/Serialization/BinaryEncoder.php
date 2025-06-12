<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

class BinaryEncoder
{
    public function encodeLong(int $value): string
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

    public function encodeFloat(float $float): string
    {
        return pack('g', $float);
    }

    public function encodeDouble(float $double): string
    {
        return pack('e', $double);
    }
}
