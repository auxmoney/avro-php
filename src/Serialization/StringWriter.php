<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class StringWriter implements WriterInterface
{
    public function __construct(
        private readonly BinaryEncoder $encoder,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(is_string($datum), 'StringWriter expects a string, got ' . gettype($datum));

        $length = $this->encoder->encodeLong(strlen($datum));
        $stream->write($length);
        $stream->write($datum);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_string($datum)) {
            $context?->addError('expected string, got ' . gettype($datum));
            return false;
        }

        return true;
    }
}
