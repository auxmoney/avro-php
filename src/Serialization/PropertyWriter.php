<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

readonly class PropertyWriter implements WriterInterface
{
    public function __construct(
        public WriterInterface $typeWriter,
        public string $name,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $this->typeWriter->write($datum, $stream);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        return $this->typeWriter->validate($datum, $context);
    }
}
