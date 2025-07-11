<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\StringBufferInterface;

class WritableStringBuffer implements StringBufferInterface
{
    /** @var array<string> */
    private array $buffer = [];

    public function __toString(): string
    {
        return implode('', $this->buffer);
    }

    public function write(string $datum): int
    {
        $this->buffer[] = $datum;

        return strlen($datum);
    }
}
