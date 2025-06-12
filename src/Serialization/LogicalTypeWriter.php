<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class LogicalTypeWriter implements WriterInterface
{
    public function __construct(
        private readonly WriterInterface $rawWriter,
        private readonly LogicalTypeInterface $logicalType,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $normalized = $this->logicalType->normalize($datum);

        $this->rawWriter->write($normalized, $stream);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        return $this->logicalType->validate($datum, $context);
    }
}
