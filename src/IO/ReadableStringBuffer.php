<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class ReadableStringBuffer implements ReadableStreamInterface
{
    private int $position = 0;

    public function __construct(
        private readonly string $buffer,
    ) {
    }

    public function read(int $count): string
    {
        $result = substr($this->buffer, $this->position, $count);
        $this->position += $count;

        return $result;
    }

    public function skip(int $count): void
    {
        $this->position += $count;
    }
}
