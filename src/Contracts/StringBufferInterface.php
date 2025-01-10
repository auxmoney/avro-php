<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface StringBufferInterface extends WritableStreamInterface
{
    public function __toString(): string;
}
