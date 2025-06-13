<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class LogicalTypeReader implements ReaderInterface
{
    public function __construct(
        private readonly ReaderInterface $rawReader,
        private readonly LogicalTypeInterface $logicalType,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $value = $this->rawReader->read($stream);

        return $this->logicalType->denormalize($value);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->rawReader->skip($stream);
    }
}
