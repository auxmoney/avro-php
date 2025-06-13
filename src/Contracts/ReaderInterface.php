<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface ReaderInterface
{
    public function read(ReadableStreamInterface $stream): mixed;

    public function skip(ReadableStreamInterface $stream): void;
}
