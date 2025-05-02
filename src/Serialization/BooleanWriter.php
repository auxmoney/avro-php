<?php

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class BooleanWriter implements WriterInterface
{
    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $stream->write($datum ? chr(1) : chr(0));
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_bool($datum)) {
            $context?->addError('expected boolean, got ' . gettype($datum));
            return false;
        }

        return true;
    }
}