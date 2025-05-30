<?php

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class FloatWriter implements WriterInterface
{
    public function __construct(private readonly BinaryEncoder $encoder)
    {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $stream->write($this->encoder->encodeFloat($datum));
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_int($datum) && !is_float($datum)) {
            $context?->addError('expected int or float, got ' . gettype($datum));
            return false;
        }

        return true;
    }
}