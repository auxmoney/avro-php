<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class BooleanReader implements ReaderInterface
{
    public function read(ReadableStreamInterface $stream): mixed
    {
        $byte = $stream->read(1);

        return $byte !== "\0";
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $stream->skip(1);
    }
}
