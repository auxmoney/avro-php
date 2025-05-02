<?php

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

readonly class PropertyReader implements ReaderInterface
{
    public function __construct(
        public ReaderInterface $typeReader,
        public string $name,
        public bool $hasDefault,
        public mixed $default,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $value = $this->typeReader->read($stream);
        if ($value === null && $this->hasDefault) {
            return $this->default;
        }

        return $value;
    }
}