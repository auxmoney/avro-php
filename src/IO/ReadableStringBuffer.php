<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Exceptions\StreamReadException;

class ReadableStringBuffer implements ReadableStreamInterface
{
    private int $position = 0;

    public function __construct(
        private readonly string $buffer,
    ) {
    }

    public function read(int $count): string
    {
        $this->validateReadOperation($count);

        $result = substr($this->buffer, $this->position, $count);
        $this->position += $count;

        return $result;
    }

    public function skip(int $count): void
    {
        $this->validateReadOperation($count);

        $this->position += $count;
    }

    /**
     * @throws StreamReadException
     */
    private function validateReadOperation(int $count): void
    {
        if ($count < 0) {
            throw new StreamReadException('Negative number of bytes is not allowed');
        }

        if ($this->position + $count > strlen($this->buffer)) {
            throw new StreamReadException(
                sprintf(
                    'Stream exhausted: requested %d bytes but only %d bytes remain',
                    $count,
                    strlen($this->buffer) - $this->position,
                ),
            );
        }
    }
}
