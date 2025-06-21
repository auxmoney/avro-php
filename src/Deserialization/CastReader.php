<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Closure;

class CastReader implements ReaderInterface
{
    public function __construct(
        public readonly ReaderInterface $typeReader,
        public readonly Closure $cast,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $value = $this->typeReader->read($stream);
        return ($this->cast)($value);
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->typeReader->skip($stream);
    }
}
