<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\ValidationContext;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\DataMismatchException;

class ValidatorWriter implements WriterInterface
{
    public function __construct(
        private readonly WriterInterface $inner,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $context = new ValidationContext();
        if (!$this->validate($datum, $context)) {
            throw new DataMismatchException($context->getErrors());
        }

        $this->inner->write($datum, $stream);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        return $this->inner->validate($datum, $context);
    }
}
