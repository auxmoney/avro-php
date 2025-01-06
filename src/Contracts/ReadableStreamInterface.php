<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface ReadableStreamInterface
{
    public function read(int $count): string;

    public function skip(int $count): void;
}
