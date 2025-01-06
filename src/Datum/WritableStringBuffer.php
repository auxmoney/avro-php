<?php

namespace Auxmoney\Avro\Datum;

use Auxmoney\Avro\Contracts\StringBufferInterface;

class WritableStringBuffer implements StringBufferInterface
{
    /** @var array<string> */
    private array $buffer = [];

    public function write(string $data): void
    {
        $this->buffer[] = $data;
    }

    public function __toString(): string
    {
        return implode('', $this->buffer);
    }
}
