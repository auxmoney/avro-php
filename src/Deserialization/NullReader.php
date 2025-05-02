<?php

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class NullReader implements ReaderInterface
{
    public function read(ReadableStreamInterface $stream): mixed
    {
        return null;
    }
}