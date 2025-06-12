<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class NullWriter implements WriterInterface
{
    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if ($datum !== null) {
            $context?->addError('expected null, got ' . gettype($datum));
            return false;
        }

        return true;
    }
}
