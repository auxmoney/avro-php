<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface WriterInterface
{
    public function write(mixed $data, WritableStreamInterface $stream): void;
}
