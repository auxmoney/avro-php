<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class FixedWriter implements WriterInterface
{
    public function __construct(
        private readonly int $size,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(is_string($datum), 'StringWriter expects a string, got ' . gettype($datum));
        assert(strlen($datum) === $this->size, 'StringWriter expects a string of length ' . $this->size . ', got ' . strlen($datum));

        $stream->write($datum);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_string($datum)) {
            $context?->addError('expected string, got ' . gettype($datum));
            return false;
        }

        if (strlen($datum) !== $this->size) {
            $context?->addError('expected string of length ' . $this->size . ', got ' . strlen($datum));
            return false;
        }

        return true;
    }
}
